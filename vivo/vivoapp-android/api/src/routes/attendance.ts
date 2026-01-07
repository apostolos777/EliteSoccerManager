import { Router } from 'express';
import { listAttendanceForEvent, addAttendance } from '../repositories/attendance';
import { requireAuth } from '../middleware/authMiddleware';

const router = Router();

// Get attendance for an event
router.get('/event/:id', async (req, res) => {
  const eventId = parseInt(req.params.id, 10);
  const records = await listAttendanceForEvent(eventId);
  res.json({ attendance: records });
});

router.post('/', requireAuth, async (req: any, res) => {
  const { player_id, event_id, status } = req.body;
  if (!player_id || !event_id) return res.status(400).json({ message: 'player_id and event_id are required' });
  try {
    // Basic uniqueness check: prevent duplicate attendance rows for same player & event
    const existing = await (await import('../db')).default('attendance').where({ player_id, event_id }).first();
    if (existing) {
      // update status/notes
      await (await import('../db')).default('attendance').where({ id: existing.id }).update({ status: status || existing.status });
      const updated = await (await import('../db')).default('attendance').where({ id: existing.id }).first();
      return res.json({ attendance: updated });
    }
    const saved = await addAttendance({ player_id, event_id, status });
    res.status(201).json({ attendance: saved });
  } catch (e:any) {
    res.status(500).json({ message: e.message });
  }
});

export default router;
