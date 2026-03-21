import crypto from 'crypto';
import { MongoClient } from 'mongodb';
import { env } from '../config/env.js';

const memoryUsers = new Map();
let collection;

async function initMongo() {
  if (!env.mongoUri || collection) return;
  const client = new MongoClient(env.mongoUri);
  await client.connect();
  const db = client.db(process.env.MONGO_DB || 'oauth_app');
  collection = db.collection('users');
  await collection.createIndex({ provider: 1, providerId: 1 }, { unique: true });
  await collection.createIndex({ email: 1 });
}

export async function findUser({ provider, providerId, email }) {
  await initMongo();

  if (collection) {
    const byProvider = await collection.findOne({ provider, providerId });
    if (byProvider) return byProvider;
    if (email) return collection.findOne({ email });
    return null;
  }

  for (const user of memoryUsers.values()) {
    if (user.provider === provider && user.providerId === providerId) return user;
    if (email && user.email === email) return user;
  }
  return null;
}

export async function createUser(user) {
  await initMongo();
  const payload = { ...user, id: crypto.randomUUID(), createdAt: new Date().toISOString() };

  if (collection) {
    await collection.insertOne(payload);
    return payload;
  }

  memoryUsers.set(payload.id, payload);
  return payload;
}

export async function upsertUser(profile) {
  const existing = await findUser(profile);
  if (existing) return { user: existing, created: false };

  const user = await createUser(profile);
  return { user, created: true };
}
