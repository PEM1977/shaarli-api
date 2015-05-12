DROP TABLE IF EXISTS "RiverEntries";

CREATE TABLE "RiverEntries" (
        "id" serial NOT NULL ,
        "date" timestamp with time zone ,
        "permalink" text NOT NULL ,
        "title" text NOT NULL ,
        "content" text ,
        "categories" text ,
        "hash" text NOT NULL ,
        "created_at" timestamp with time zone NOT NULL DEFAULT NOW(),
        "feed_id" integer NOT NULL ,
        PRIMARY KEY ("id")

);
DROP INDEX IF EXISTS "entries_feed_id";
CREATE INDEX "entries_feed_id" ON "RiverEntries" ("feed_id");
DROP INDEX IF EXISTS "entries_date";
CREATE INDEX "entries_date" ON "RiverEntries" ("date");

DROP TABLE IF EXISTS "RiverFeeds";

CREATE TABLE "RiverFeeds" (
        "id" serial NOT NULL ,
        "url" text NOT NULL ,
        "https" smallint ,
        "link" text ,
        "title" text ,
        "error" text ,
        "enabled" smallint NOT NULL ,
        "fetch_interval" integer NOT NULL ,
        "fetched_at" timestamp with time zone ,
        "created_at" timestamp with time zone NOT NULL DEFAULT NOW(),
        PRIMARY KEY ("id")

);
DROP INDEX IF EXISTS "feeds_feeds_url_unique";
CREATE UNIQUE INDEX "feeds_feeds_url_unique" ON "RiverFeeds" ("url");
