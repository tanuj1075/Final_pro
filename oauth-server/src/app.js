import express from 'express';
import cookieParser from 'cookie-parser';
import authRouter from './routes/auth.js';
import { OAuthError } from './utils/errors.js';

const app = express();

app.use(express.json());
app.use(cookieParser());

app.get('/health', (req, res) => {
  res.status(200).json({ ok: true });
});

app.use(authRouter);

app.use((err, req, res, next) => {
  if (err instanceof OAuthError) {
    return res.status(err.status).json({ error: err.message, details: err.details || null });
  }

  console.error(err);
  return res.status(500).json({ error: 'Internal server error' });
});

export default app;
