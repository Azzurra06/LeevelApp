-- Remises : par élève (pourcentage + motif) et détail sur chaque échéance
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS discount_pct DECIMAL(5,2) NULL,
  ADD COLUMN IF NOT EXISTS discount_label VARCHAR(80) NULL;
ALTER TABLE payments
  ADD COLUMN IF NOT EXISTS gross DECIMAL(8,2) NULL AFTER minutes,
  ADD COLUMN IF NOT EXISTS discount DECIMAL(8,2) NULL AFTER gross,
  ADD COLUMN IF NOT EXISTS discount_label VARCHAR(80) NULL AFTER discount;
