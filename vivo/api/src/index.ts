import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';

import authRoutes from './routes/auth';
import playersRoutes from './routes/players';
import teamsRoutes from './routes/teams';
import attendanceRoutes from './routes/attendance';
import eventsRoutes from './routes/events';

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

app.use('/auth', authRoutes);
app.use('/players', playersRoutes);
app.use('/teams', teamsRoutes);
app.use('/attendance', attendanceRoutes);
app.use('/events', eventsRoutes);

const port = process.env.PORT || 4000;
app.listen(port, () => {
  console.log(`Vivo API listening on port ${port}`);
});
