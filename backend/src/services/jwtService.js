import jwt from 'jsonwebtoken';
import { env } from '../config/env.js';

export function issueAuthToken(user) {
  return jwt.sign(
    {
      sub: user.id,
      email: user.email,
      name: user.name,
      provider: user.provider,
    },
    env.jwtSecret,
    { expiresIn: '7d' }
  );
}
