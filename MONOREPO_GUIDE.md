# Clean Project Structure

```text
project/
│
├── frontend/        # UI (what users see)
│   ├── app/         # Pages (Next.js)
│   ├── components/  # Reusable UI parts
│   ├── services/    # API calls
│   ├── public/
│   │   ├── images/  # Images (jpg, png)
│   │   └── videos/  # Videos (mp4)
│   └── .env.local
│
├── backend/         # Server logic
│   ├── routes/      # API routes
│   ├── controllers/ # Logic handlers
│   ├── services/    # OAuth, helpers
│   ├── middleware/  # Auth checks
│   ├── config/      # DB & env config
│   └── .env
│
├── database/        # Data layer
│   ├── models/      # User model
│   ├── config/      # DB connection
│   └── schema/      # Structure
│
└── README.md        # Simple guide
```
