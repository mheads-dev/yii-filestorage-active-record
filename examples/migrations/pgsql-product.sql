CREATE TABLE product (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  picture_id BIGINT NULL,
  manual_id BIGINT NULL
);

CREATE INDEX idx_product_picture_id ON product (picture_id);
CREATE INDEX idx_product_manual_id ON product (manual_id);
