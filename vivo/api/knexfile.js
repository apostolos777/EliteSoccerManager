require('dotenv').config();

module.exports = {
  client: process.env.DB_CLIENT || 'mysql2',
  connection: {
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'vivo'
  },
  migrations: {
    directory: __dirname + '/migrations'
  }
};
