import db from '../db';

export async function listPlayers() {
  return await db('players').select('id','name','position','team_id','jersey_number','age','status');
}

export async function getPlayer(id:number) {
  return await db('players').where({ id }).first();
}

export async function createPlayer(data:any) {
  const [id] = await db('players').insert(data);
  return getPlayer(id);
}
