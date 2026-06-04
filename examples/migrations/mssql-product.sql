CREATE TABLE [product] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [name] NVARCHAR(255) NOT NULL,
  [picture_id] BIGINT NULL,
  [manual_id] BIGINT NULL,
  CONSTRAINT [PK_product] PRIMARY KEY ([id])
);

CREATE INDEX [idx_product_picture_id] ON [product]([picture_id]);
CREATE INDEX [idx_product_manual_id] ON [product]([manual_id]);
