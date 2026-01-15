import db from '../db';

export async function listEvents() {
  return await db('events').select('*').orderBy('event_date','asc');
}

export async function createEvent(data:any) {
  const [id] = await db('events').insert(data);
  return db('events').where({ id }).first();
}
