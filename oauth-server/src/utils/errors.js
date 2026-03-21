export class OAuthError extends Error {
  constructor(message, status = 400, details) {
    super(message);
    this.name = 'OAuthError';
    this.status = status;
    this.details = details;
  }
}
