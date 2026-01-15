import { Router } from 'express';
import { listAttendanceForEvent, addAttendance } from '../repositories/attendance';

const router = Router();

// Get attendance for an event
router.get('/event/:id', async (req, res) => {
  const eventId = parseInt(req.params.id, 10);
  const records = await listAttendanceForEvent(eventId);
  res.json({ attendance: records });
});

import { requireAuth } from '../middleware/authMiddleware';

router.post('/', requireAuth, async (req, res) => {
  const record = req.body;
  const saved = await addAttendance(record);
  res.status(201).json({ attendance: saved });
});

export default router;
