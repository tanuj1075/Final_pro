import express from 'express';
import cors from 'cors';
import cookieParser from 'cookie-parser';
import authRoutes from './routes/auth.js';
import { env } from './config/env.js';

const app = express();

app.use(cors({ origin: env.frontendUrl, credentials: true }));
app.use(cookieParser());
app.use(express.json());

app.get('/health', (req, res) => {
  res.json({ ok: true });
});

app.use('/auth', authRoutes);

app.listen(env.port, () => {
  console.log(`Backend running at http://localhost:${env.port}`);
});
