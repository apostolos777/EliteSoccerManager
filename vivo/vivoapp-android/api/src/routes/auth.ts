import { Router } from 'express';
import jwt from 'jsonwebtoken';
import { findUserByEmail, verifyPassword } from '../repositories/auth';

const router = Router();

router.post('/login', async (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) return res.status(400).json({ message: 'Email and password required' });

  const user = await findUserByEmail(email.toLowerCase());
  if (!user) return res.status(401).json({ message: 'Invalid credentials' });

  const ok = await verifyPassword(user, password);
  if (!ok) return res.status(401).json({ message: 'Invalid credentials' });

  const token = jwt.sign({ sub: user.id, role: user.role }, process.env.JWT_SECRET || 'dev-secret', { expiresIn: '7d' });
  res.json({ token, user: { id: user.id, email: user.email, role: user.role } });
});

export default router;
