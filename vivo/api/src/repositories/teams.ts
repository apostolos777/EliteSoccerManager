import db from '../db';

export async function listTeams() {
  return await db('teams').select('id','name','age_group','contact_email');
}

export async function getTeam(id:number) {
  return await db('teams').where({ id }).first();
}

export async function createTeam(data:any) {
  const [id] = await db('teams').insert(data);
  return getTeam(id);
}
