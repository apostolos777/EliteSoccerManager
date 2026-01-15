import db from '../db';
import bcrypt from 'bcrypt';

export async function findUserByEmail(email:string) {
  return await db('users').where({ email }).first();
}

export async function verifyPassword(user:any, password:string) {
  if (!user) return false;
  return await bcrypt.compare(password, user.password_hash);
}

export async function createUser(data:any) {
  const hash = await bcrypt.hash(data.password, 10);
  const insert = {
    email: data.email,
    username: data.username,
    password_hash: hash,
    role: data.role || 'player',
    player_id: data.player_id || null
  };
  const [id] = await db('users').insert(insert);
  return db('users').where({ id }).first();
}
