import { findOrCreateUserFromOAuth } from '../../../database/src/userModel.js';

export async function upsertOAuthUser(profile) {
  return findOrCreateUserFromOAuth(profile);
}
