import crypto from 'crypto';
import { env } from '../config/env.js';

const states = new Map();

export function issueState(provider) {
  const state = crypto.randomBytes(24).toString('hex');
  states.set(state, { provider, expiresAt: Date.now() + env.stateTtlMs });
  return state;
}

export function consumeState(state, provider) {
  if (!state) return false;
  const entry = states.get(state);
  states.delete(state);

  if (!entry) return false;
  if (entry.provider !== provider) return false;
  if (entry.expiresAt < Date.now()) return false;
  return true;
}

setInterval(() => {
  const now = Date.now();
  for (const [key, value] of states.entries()) {
    if (value.expiresAt < now) states.delete(key);
  }
}, 60_000).unref();
