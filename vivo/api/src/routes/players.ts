import { Router } from 'express';
import { listPlayers, getPlayer } from '../repositories/players';

const router = Router();

router.get('/', async (req, res) => {
  const players = await listPlayers();
  res.json({ players });
});

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const player = await getPlayer(id);
  if (!player) return res.status(404).json({ message: 'Player not found' });
  res.json({ player });
});

export default router;
