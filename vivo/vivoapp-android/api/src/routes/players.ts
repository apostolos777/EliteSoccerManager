import { Router } from 'express';
import db from '../db';
import { listPlayers, getPlayer, createPlayer } from '../repositories/players';
import { requireAuth } from '../middleware/authMiddleware';

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

// Create player (authenticated users)
router.post('/', requireAuth, async (req: any, res) => {
  const { name, position, team_id, jersey_number, age, team_ids } = req.body;
  if (!name) return res.status(400).json({ message: 'Player name is required' });
  try {
    const p = await createPlayer({ name, position, team_id, jersey_number, age });
    // Handle optional many-to-many team_ids
    if (Array.isArray(team_ids) && team_ids.length) {
      const inserts = team_ids.map((t:any) => ({ player_id: p.id, team_id: t }));
      // Insert ignoring duplicates where possible
      await Promise.all(inserts.map(async (row) => {
        try { await db('player_teams').insert(row); } catch (e) { /* ignore */ }
      }));
    }
    res.status(201).json({ player: p });
  } catch (e:any) {
    res.status(500).json({ message: e.message });
  }
});

export default router;
