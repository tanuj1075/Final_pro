import dotenv from 'dotenv';
import pg from 'pg';

dotenv.config({ path: new URL('../.env', import.meta.url).pathname });
dotenv.config();

const { Pool } = pg;

if (!process.env.DATABASE_URL) {
  throw new Error('Missing DATABASE_URL');
}

export const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});
