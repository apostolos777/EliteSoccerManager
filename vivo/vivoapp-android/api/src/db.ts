import knex from 'knex';
const config: any = require('../knexfile');

const env = process.env.NODE_ENV || 'development';
const db = knex(config);

export default db;
