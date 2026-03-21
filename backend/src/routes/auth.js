import { Router } from 'express';
import { googleStart, googleCallback } from '../controllers/authController.js';

const router = Router();

router.get('/auth/google', googleStart);
router.get('/auth/callback', googleCallback);

export default router;
