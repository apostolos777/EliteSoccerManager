import db from '../db';

export async function listAttendanceForEvent(eventId:number) {
  return await db('attendance').where({ event_id: eventId }).select('*');
}

export async function addAttendance(record:any) {
  const [id] = await db('attendance').insert(record);
  return db('attendance').where({ id }).first();
}
