CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS stories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    generation_id INTEGER UNIQUE,
    title TEXT NOT NULL,
    premise TEXT NOT NULL,
    genre TEXT NOT NULL,
    page_count INTEGER NOT NULL CHECK (page_count IN (1, 2, 3)),
    pages_json TEXT NOT NULL,
    is_public INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    story_id INTEGER NOT NULL REFERENCES stories(id) ON DELETE CASCADE,
    text TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS generations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    premise TEXT NOT NULL,
    genre TEXT NOT NULL,
    page_count INTEGER NOT NULL CHECK (page_count IN (1, 2, 3)),
    title TEXT NOT NULL DEFAULT '',
    outline_json TEXT NOT NULL DEFAULT '[]',
    pages_json TEXT NOT NULL DEFAULT '[]',
    status TEXT NOT NULL DEFAULT 'pending',
    error TEXT NOT NULL DEFAULT '',
    locked_until INTEGER NOT NULL DEFAULT 0,
    story_id INTEGER REFERENCES stories(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS generation_calls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    generation_id INTEGER NOT NULL REFERENCES generations(id) ON DELETE CASCADE,
    outcome TEXT NOT NULL DEFAULT 'started',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS stories_public_date ON stories(is_public, created_at);
CREATE INDEX IF NOT EXISTS stories_user ON stories(user_id);
CREATE INDEX IF NOT EXISTS comments_story ON comments(story_id, id);
CREATE INDEX IF NOT EXISTS generations_user ON generations(user_id, id);
CREATE INDEX IF NOT EXISTS generation_calls_date ON generation_calls(created_at);
