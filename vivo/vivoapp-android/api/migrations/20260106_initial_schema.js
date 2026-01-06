exports.up = async function(knex) {
  // Teams
  if (!(await knex.schema.hasTable('teams'))) {
    await knex.schema.createTable('teams', (t) => {
      t.increments('id').primary();
      t.string('name').notNullable();
      t.string('coach_name');
      t.string('assistant_coach');
      t.text('description');
      t.string('age_group');
      t.string('contact_email');
      t.string('contact_phone');
      t.string('photo_url');
      t.timestamps(true, true);
    });
  }

  // Coaches
  if (!(await knex.schema.hasTable('coaches'))) {
    await knex.schema.createTable('coaches', (t) => {
      t.increments('id').primary();
      t.string('name').notNullable();
      t.string('email');
      t.string('phone');
      t.string('role').defaultTo('coach');
      t.text('qualifications');
      t.text('certifications');
      t.text('bio');
      t.string('photo_url');
      t.string('status').defaultTo('active');
      t.timestamps(true, true);
    });
  }

  // Team coaches (join)
  if (!(await knex.schema.hasTable('team_coaches'))) {
    await knex.schema.createTable('team_coaches', (t) => {
      t.increments('id').primary();
      t.integer('team_id').unsigned().notNullable().references('id').inTable('teams').onDelete('CASCADE');
      t.integer('coach_id').unsigned().notNullable().references('id').inTable('coaches').onDelete('CASCADE');
      t.string('role_in_team').defaultTo('coach');
      t.boolean('is_primary').defaultTo(false);
      t.timestamps(true, true);
      t.unique(['team_id','coach_id']);
    });
  }

  // Players
  if (!(await knex.schema.hasTable('players'))) {
    await knex.schema.createTable('players', (t) => {
      t.increments('id').primary();
      t.string('name').notNullable();
      t.string('position');
      t.integer('team_id').unsigned().references('id').inTable('teams');
      t.integer('jersey_number');
      t.integer('age');
      t.string('photo_url');
      t.string('status').defaultTo('active');
      t.timestamps(true, true);
    });
  }

  // Player-teams join (multi-team support)
  if (!(await knex.schema.hasTable('player_teams'))) {
    await knex.schema.createTable('player_teams', (t) => {
      t.increments('id').primary();
      t.integer('player_id').unsigned().notNullable().references('id').inTable('players').onDelete('CASCADE');
      t.integer('team_id').unsigned().notNullable().references('id').inTable('teams').onDelete('CASCADE');
      t.timestamps(true, true);
      t.unique(['player_id','team_id']);
    });
  }

  // Users table
  if (!(await knex.schema.hasTable('users'))) {
    await knex.schema.createTable('users', (t) => {
      t.increments('id').primary();
      t.string('email').notNullable().unique();
      t.string('username').unique();
      t.string('password_hash').notNullable();
      t.string('role').defaultTo('player');
      t.integer('player_id').unsigned().references('id').inTable('players').onDelete('SET NULL');
      t.string('status').defaultTo('active');
      t.timestamps(true, true);
    });
  }

  // Events
  if (!(await knex.schema.hasTable('events'))) {
    await knex.schema.createTable('events', (t) => {
      t.increments('id').primary();
      t.string('title').notNullable();
      t.text('description');
      t.dateTime('event_date');
      t.string('location');
      t.string('event_type').defaultTo('match');
      t.string('status').defaultTo('scheduled');
      t.string('age_group');
      t.integer('team_id').unsigned().references('id').inTable('teams');
      t.string('opponent');
      t.boolean('is_home_game').defaultTo(false);
      t.integer('max_participants');
      t.decimal('cost', 10, 2);
      t.text('equipment_needed');
      t.text('notes');
      t.boolean('is_mandatory').defaultTo(false);
      t.integer('team_score');
      t.integer('opponent_score');
      t.timestamps(true, true);
    });
  }

  // Attendance
  if (!(await knex.schema.hasTable('attendance'))) {
    await knex.schema.createTable('attendance', (t) => {
      t.increments('id').primary();
      t.integer('player_id').unsigned().notNullable().references('id').inTable('players').onDelete('CASCADE');
      t.integer('event_id').unsigned().notNullable().references('id').inTable('events').onDelete('CASCADE');
      t.string('status').defaultTo('present');
      t.text('notes');
      t.dateTime('recorded_at').defaultTo(knex.fn.now());
    });
  }

  // Club settings
  if (!(await knex.schema.hasTable('club_settings'))) {
    await knex.schema.createTable('club_settings', (t) => {
      t.increments('id').primary();
      t.string('setting_key').notNullable().unique();
      t.text('setting_value');
      t.timestamps(true, true);
    });
  }

  // Player documents (optional)
  if (!(await knex.schema.hasTable('player_documents'))) {
    await knex.schema.createTable('player_documents', (t) => {
      t.increments('id').primary();
      t.integer('player_id').unsigned().notNullable().references('id').inTable('players').onDelete('CASCADE');
      t.string('document_type');
      t.string('filename');
      t.string('file_path');
      t.string('mime_type');
      t.integer('file_size');
      t.timestamps(true, true);
    });
  }

  // Player stats (basic)
  if (!(await knex.schema.hasTable('player_stats'))) {
    await knex.schema.createTable('player_stats', (t) => {
      t.increments('id').primary();
      t.integer('player_id').unsigned().notNullable().references('id').inTable('players').onDelete('CASCADE');
      t.integer('goals').defaultTo(0);
      t.integer('assists').defaultTo(0);
      t.integer('appearances').defaultTo(0);
      t.timestamps(true, true);
    });
  }
};

exports.down = async function(knex) {
  // Drop in reverse order to avoid FK issues
  await knex.schema.dropTableIfExists('player_stats');
  await knex.schema.dropTableIfExists('player_documents');
  await knex.schema.dropTableIfExists('club_settings');
  await knex.schema.dropTableIfExists('attendance');
  await knex.schema.dropTableIfExists('events');
  await knex.schema.dropTableIfExists('users');
  await knex.schema.dropTableIfExists('player_teams');
  await knex.schema.dropTableIfExists('players');
  await knex.schema.dropTableIfExists('team_coaches');
  await knex.schema.dropTableIfExists('coaches');
  await knex.schema.dropTableIfExists('teams');
};