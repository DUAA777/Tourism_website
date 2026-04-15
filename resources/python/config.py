import os
import sqlite3
from pathlib import Path

try:
    import pymysql
except ImportError:
    pymysql = None


PROJECT_ROOT = Path(__file__).resolve().parents[2]
ENV_PATH = PROJECT_ROOT / ".env"


def load_env_file(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}

    if not path.exists():
        return values

    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"').strip("'")

    return values


ENV = load_env_file(ENV_PATH)

DB_CONNECTION = os.getenv("DB_CONNECTION", ENV.get("DB_CONNECTION", "sqlite")).lower()
DB_DATABASE = os.getenv("DB_DATABASE", ENV.get("DB_DATABASE", "database/database.sqlite"))

if DB_CONNECTION == "sqlite":
    database_path = Path(DB_DATABASE)
    if not database_path.is_absolute():
        database_path = (PROJECT_ROOT / database_path).resolve()

    DB_CONFIG = {
        "connection": "sqlite",
        "database": str(database_path),
    }
else:
    DB_CONFIG = {
        "connection": "mysql",
        "host": os.getenv("DB_HOST", ENV.get("DB_HOST", "127.0.0.1")),
        "port": int(os.getenv("DB_PORT", ENV.get("DB_PORT", "3306"))),
        "user": os.getenv("DB_USERNAME", ENV.get("DB_USERNAME", "root")),
        "password": os.getenv("DB_PASSWORD", ENV.get("DB_PASSWORD", "")),
        "database": os.getenv("DB_DATABASE", ENV.get("DB_DATABASE", "")),
        "charset": "utf8mb4",
        "cursorclass": pymysql.cursors.DictCursor if pymysql else None,
    }


def get_connection():
    if DB_CONFIG["connection"] == "sqlite":
        return sqlite3.connect(DB_CONFIG["database"])

    if pymysql is None:
        raise RuntimeError(
            "PyMySQL is required for MySQL mode, but it is not installed. "
            "Either install pymysql or switch the Laravel app to SQLite."
        )

    connection_config = {k: v for k, v in DB_CONFIG.items() if k != "connection" and v is not None}
    return pymysql.connect(**connection_config)
