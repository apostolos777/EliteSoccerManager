import db from '../db';
import bcrypt from 'bcrypt';

async function seed() {
  const email = process.env.ADMIN_EMAIL || 'admin@example.com';
  const pw = process.env.ADMIN_PW || 'password123';
  const existing = await db('users').where({ email }).orWhere({ username: 'admin' }).first();
  if (existing) {
    console.log('Admin already exists');
    process.exit(0);
  }
  const hash = await bcrypt.hash(pw, 10);
  const info = await db('users').columnInfo();
  const insertObj: any = { email, username: 'admin', role: 'admin' };
  if (info && Object.prototype.hasOwnProperty.call(info, 'password_hash')) {
    insertObj.password_hash = hash;
  } else {
    // fall back to legacy `password` column
    insertObj.password = hash;
  }
  const [id] = await db('users').insert(insertObj);
  console.log('Created admin user id', id);
  process.exit(0);
}

seed().catch(err => { console.error(err); process.exit(1); });