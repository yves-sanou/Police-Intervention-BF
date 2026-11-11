-- Migration à exécuter une seule fois sur une base V5 déjà installée.
ALTER TABLE sos_alerts
    ADD COLUMN intervention_id BIGINT UNSIGNED NULL AFTER resolved_at,
    ADD CONSTRAINT fk_sos_intervention
        FOREIGN KEY (intervention_id) REFERENCES interventions(id)
        ON DELETE SET NULL;
