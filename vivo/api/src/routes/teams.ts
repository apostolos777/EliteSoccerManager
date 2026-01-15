import { Router } from 'express';
import { listTeams, getTeam } from '../repositories/teams';

const router = Router();

router.get('/', async (req, res) => {
  const teams = await listTeams();
  res.json({ teams });
});

router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const team = await getTeam(id);
  if (!team) return res.status(404).json({ message: 'Team not found' });
  res.json({ team });
});

export default router;
