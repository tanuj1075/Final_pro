import { pool } from './client.js';

export async function findByProviderIdentity(provider, providerId) {
  const { rows } = await pool.query(
    `SELECT * FROM users WHERE provider = $1 AND provider_id = $2 LIMIT 1`,
    [provider, providerId]
  );
  return rows[0] || null;
}

export async function findByEmail(email) {
  const { rows } = await pool.query('SELECT * FROM users WHERE email = $1 LIMIT 1', [email]);
  return rows[0] || null;
}

export async function createOAuthUser(profile) {
  const { rows } = await pool.query(
    `INSERT INTO users (email, name, provider, provider_id, avatar_url, is_approved, is_active)
     VALUES ($1, $2, $3, $4, $5, TRUE, TRUE)
     RETURNING *`,
    [profile.email, profile.name || '', profile.provider, profile.providerId, profile.avatar]
  );
  return rows[0];
}

export async function findOrCreateUserFromOAuth(profile) {
  const byIdentity = await findByProviderIdentity(profile.provider, profile.providerId);
  if (byIdentity) return { user: byIdentity, created: false };

  const byEmail = await findByEmail(profile.email);
  if (byEmail) {
    if (!byEmail.provider_id || byEmail.provider === profile.provider) {
      const { rows } = await pool.query(
        `UPDATE users SET provider = $1, provider_id = $2, avatar_url = COALESCE($3, avatar_url) WHERE id = $4 RETURNING *`,
        [profile.provider, profile.providerId, profile.avatar, byEmail.id]
      );
      return { user: rows[0], created: false };
    }
    throw new Error('OAuth conflict: email already linked to another provider identity');
  }

  const createdUser = await createOAuthUser(profile);
  return { user: createdUser, created: true };
}
