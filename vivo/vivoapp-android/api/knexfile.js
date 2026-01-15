require('dotenv').config();

const client = process.env.DB_CLIENT || 'mysql2';

const common = {
  migrations: {
    directory: __dirname + '/migrations'
  }
};

if (client === 'sqlite3') {
  module.exports = Object.assign({}, common, {
    client: 'sqlite3',
    connection: {
      filename: process.env.DB_FILENAME || __dirname + '/../database.db'
    },
    useNullAsDefault: true
  });
} else {
  module.exports = Object.assign({}, common, {
    client: client,
    connection: {
      host: process.env.DB_HOST || '127.0.0.1',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || '',
      database: process.env.DB_NAME || 'vivo'
    }
  });
}
