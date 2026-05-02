CREATE TYPE "user_role_enum" AS ENUM (
  'admin',
  'kitchen_staff',
  'user'
);

CREATE TYPE "order_status" AS ENUM (
  'pending',
  'preparing',
  'ready',
  'served',
  'canceled'
);

CREATE TYPE "message_tag" AS ENUM (
  'item_requirement',
  'customer_inquiry',
  'staff_duty',
  'incident',
  'other'
);

CREATE TYPE "message_priority" AS ENUM (
  'high',
  'medium',
  'low'
);

CREATE TYPE "stock_movement_type" AS ENUM (
  'initial',
  'restock',
  'sale',
  'adjustment',
  'damage',
  'return'
);

CREATE TABLE "roles" (
  "id" bigserial PRIMARY KEY,
  "name" varchar(50) UNIQUE NOT NULL,
  "description" varchar(255),
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now())
);

CREATE TABLE "tenants" (
  "id" bigserial PRIMARY KEY,
  "name" varchar(150) NOT NULL,
  "subscription_active" boolean NOT NULL DEFAULT true,
  "subscription_ends_at" timestamp NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "users" (
  "id" bigserial PRIMARY KEY,
  "tenant_id" bigint NOT NULL,
  "name" varchar(100) NOT NULL,
  "email" varchar(255) UNIQUE NOT NULL,
  "role_id" bigint,
  "password" varchar(255) NOT NULL,
  "role" user_role_enum NOT NULL DEFAULT 'user',
  "is_active" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "deleted_at" timestamp
);

CREATE TABLE "password_reset_tokens" (
  "id" bigserial PRIMARY KEY,
  "user_id" bigint NOT NULL,
  "token" varchar(255) UNIQUE NOT NULL,
  "expires_at" timestamp NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT (now())
);

CREATE TABLE "categories" (
  "id" bigserial PRIMARY KEY,
  "tenant_id" bigint NOT NULL,
  "name" varchar(100) NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "deleted_at" timestamp,
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "menu_items" (
  "id" bigserial PRIMARY KEY,
  "category_id" bigint NOT NULL,
  "name" varchar(150) NOT NULL,
  "description" text,
  "image_path" varchar(500),
  "price" decimal(8,2) NOT NULL,
  "needs_restock" boolean NOT NULL DEFAULT false,
  "requested_restock_quantity" integer,
  "is_available" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "deleted_at" timestamp,
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "stock" (
  "id" bigserial PRIMARY KEY,
  "menu_item_id" bigint UNIQUE NOT NULL,
  "current_quantity" integer NOT NULL DEFAULT 0,
  "low_stock_threshold" integer NOT NULL DEFAULT 10,
  "restock_level" integer NOT NULL DEFAULT 50,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "deleted_at" timestamp,
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "stock_movements" (
  "id" bigserial PRIMARY KEY,
  "stock_id" bigint NOT NULL,
  "movement_type" stock_movement_type NOT NULL DEFAULT 'adjustment',
  "quantity_changed" integer NOT NULL,
  "previous_quantity" integer NOT NULL,
  "new_quantity" integer NOT NULL,
  "reason" varchar(255),
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "deleted_at" timestamp,
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "orders" (
  "id" bigserial PRIMARY KEY,
  "user_id" bigint NOT NULL,
  "status" order_status NOT NULL DEFAULT 'pending',
  "total_amount" decimal(10,2) NOT NULL,
  "notes" text,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "order_items" (
  "id" bigserial PRIMARY KEY,
  "order_id" bigint NOT NULL,
  "menu_item_id" bigint NOT NULL,
  "quantity" integer NOT NULL DEFAULT 1,
  "unit_price" decimal(8,2) NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "transactions" (
  "id" bigserial PRIMARY KEY,
  "order_id" bigint UNIQUE NOT NULL,
  "recorded_by" bigint NOT NULL,
  "tendered_amount" decimal(10,2) NOT NULL,
  "change_returned" decimal(10,2) NOT NULL,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

CREATE TABLE "messages" (
  "id" bigserial PRIMARY KEY,
  "tenant_id" bigint NOT NULL,
  "sender_id" bigint NOT NULL,
  "receiver_id" bigint NOT NULL,
  "title" varchar(255) NOT NULL,
  "content" text NOT NULL,
  "tag" message_tag NOT NULL DEFAULT 'other',
  "priority" message_priority NOT NULL DEFAULT 'low',
  "is_read" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NOT NULL DEFAULT (now()),
  "updated_at" timestamp NOT NULL DEFAULT (now()),
  "created_by" bigint,
  "updated_by" bigint,
  "deleted_by" bigint
);

ALTER TABLE "tenants" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "tenants" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "tenants" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "users" ADD FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "users" ADD FOREIGN KEY ("role_id") REFERENCES "roles" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "password_reset_tokens" ADD FOREIGN KEY ("user_id") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "categories" ADD FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "categories" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "categories" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "categories" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "menu_items" ADD FOREIGN KEY ("category_id") REFERENCES "categories" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "menu_items" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "menu_items" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "menu_items" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock" ADD FOREIGN KEY ("menu_item_id") REFERENCES "menu_items" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock_movements" ADD FOREIGN KEY ("stock_id") REFERENCES "stock" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock_movements" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock_movements" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "stock_movements" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "orders" ADD FOREIGN KEY ("user_id") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "orders" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "orders" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "orders" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "order_items" ADD FOREIGN KEY ("order_id") REFERENCES "orders" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "order_items" ADD FOREIGN KEY ("menu_item_id") REFERENCES "menu_items" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "order_items" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "order_items" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "order_items" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "transactions" ADD FOREIGN KEY ("order_id") REFERENCES "orders" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "transactions" ADD FOREIGN KEY ("recorded_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "transactions" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "transactions" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "transactions" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "messages" ADD FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "messages" ADD FOREIGN KEY ("sender_id") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "messages" ADD FOREIGN KEY ("receiver_id") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "messages" ADD FOREIGN KEY ("created_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "messages" ADD FOREIGN KEY ("updated_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE "messages" ADD FOREIGN KEY ("deleted_by") REFERENCES "users" ("id") DEFERRABLE INITIALLY IMMEDIATE;
