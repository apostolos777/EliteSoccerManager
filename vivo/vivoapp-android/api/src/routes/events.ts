import { Router } from 'express';
import { listEvents, createEvent } from '../repositories/events';

const router = Router();

router.get('/', async (req, res) => {
  const events = await listEvents();
  res.json({ events });
});

import { requireAuth } from '../middleware/authMiddleware';

router.post('/', requireAuth, async (req, res) => {
  const data = req.body;
  const ev = await createEvent(data);
  res.status(201).json({ event: ev });
});

export default router;
