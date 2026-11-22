-- Modalités de paiement par famille + période couverte par chaque échéance
ALTER TABLE families
  ADD COLUMN IF NOT EXISTS pay_frequency VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS pay_method VARCHAR(30) NULL;
ALTER TABLE payments
  ADD COLUMN IF NOT EXISTS period_start DATE NULL AFTER period,
  ADD COLUMN IF NOT EXISTS period_end DATE NULL AFTER period_start;
UPDATE payments SET period_start=CONCAT(period,'-01'), period_end=LAST_DAY(CONCAT(period,'-01')) WHERE period_end IS NULL AND minutes>0 AND period IS NOT NULL;
