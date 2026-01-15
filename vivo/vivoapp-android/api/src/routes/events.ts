import { Router } from 'express';
import { listEvents, createEvent } from '../repositories/events';
import { requireAuth } from '../middleware/authMiddleware';

const router = Router();

router.get('/', async (req, res) => {
  const events = await listEvents();
  res.json({ events });
});

router.post('/', requireAuth, async (req: any, res) => {
  const { title, event_date } = req.body;
  if (!title || !event_date) return res.status(400).json({ message: 'title and event_date are required' });
  try {
    const ev = await createEvent(req.body);
    res.status(201).json({ event: ev });
  } catch (e:any) {
    res.status(500).json({ message: e.message });
  }
});

export default router;
