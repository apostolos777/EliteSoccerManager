import { Router } from 'express';
import { listTeams, getTeam, createTeam } from '../repositories/teams';
import { requireAuth } from '../middleware/authMiddleware';

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

// Create a new team (admin)
router.post('/', requireAuth, async (req: any, res) => {
  const user = req.user;
  if (!user || user.role !== 'admin') return res.status(403).json({ message: 'Admin privileges required' });
  const { name, age_group, contact_email } = req.body;
  if (!name) return res.status(400).json({ message: 'Team name is required' });
  try {
    const team = await createTeam({ name, age_group, contact_email });
    res.status(201).json({ team });
  } catch (e:any) {
    res.status(500).json({ message: e.message });
  }
});

export default router;
