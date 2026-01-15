import db from '../db';
import bcrypt from 'bcrypt';

async function seed() {
  const email = process.env.ADMIN_EMAIL || 'admin@example.com';
  const pw = process.env.ADMIN_PW || 'password123';
  const existing = await db('users').where({ email }).first();
  if (existing) {
    console.log('Admin already exists');
    process.exit(0);
  }
  const hash = await bcrypt.hash(pw, 10);
  const [id] = await db('users').insert({ email, username: 'admin', password_hash: hash, role: 'admin' });
  console.log('Created admin user id', id);
  process.exit(0);
}

seed().catch(err => { console.error(err); process.exit(1); });