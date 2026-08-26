<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->createSqliteTriggers();
        } elseif ($driver === 'mysql') {
            $this->createMysqlTriggers();
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_assignment_insert;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_assignment_update;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_assignment_delete;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_version_delete;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_version_update;');
        } elseif ($driver === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_assignment_insert;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_assignment_update;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_assignment_delete;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_version_delete;');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_published_version_update;');
        }
    }

    private function createSqliteTriggers(): void
    {
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_prevent_published_assignment_insert
            BEFORE INSERT ON schedule_assignments
            FOR EACH ROW
            WHEN (SELECT status FROM schedule_versions WHERE id = NEW.schedule_version_id) IN ('PUBLISHED', 'ARCHIVED')
            BEGIN
                SELECT RAISE(ABORT, 'Inmutabilidad violada: No se pueden insertar asignaciones en versiones PUBLISHED o ARCHIVED.');
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_prevent_published_assignment_update
            BEFORE UPDATE ON schedule_assignments
            FOR EACH ROW
            WHEN (SELECT status FROM schedule_versions WHERE id = OLD.schedule_version_id) IN ('PUBLISHED', 'ARCHIVED')
                 OR (SELECT status FROM schedule_versions WHERE id = NEW.schedule_version_id) IN ('PUBLISHED', 'ARCHIVED')
            BEGIN
                SELECT RAISE(ABORT, 'Inmutabilidad violada: No se pueden modificar ni transferir asignaciones en versiones PUBLISHED o ARCHIVED.');
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_prevent_published_assignment_delete
            BEFORE DELETE ON schedule_assignments
            FOR EACH ROW
            WHEN (SELECT status FROM schedule_versions WHERE id = OLD.schedule_version_id) IN ('PUBLISHED', 'ARCHIVED')
            BEGIN
                SELECT RAISE(ABORT, 'Inmutabilidad violada: No se pueden eliminar asignaciones en versiones PUBLISHED o ARCHIVED.');
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_prevent_published_version_delete
            BEFORE DELETE ON schedule_versions
            FOR EACH ROW
            WHEN OLD.status IN ('PUBLISHED', 'ARCHIVED')
            BEGIN
                SELECT RAISE(ABORT, 'Inmutabilidad violada: No se pueden eliminar versiones PUBLISHED o ARCHIVED.');
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS trg_prevent_published_version_update
            BEFORE UPDATE ON schedule_versions
            FOR EACH ROW
            WHEN (OLD.status = 'ARCHIVED')
                 OR (
                     OLD.status = 'PUBLISHED' AND (
                         NEW.status != 'ARCHIVED'
                         OR NEW.work_period_id != OLD.work_period_id
                         OR NEW.version_number != OLD.version_number
                         OR NOT (NEW.parent_version_id IS OLD.parent_version_id)
                         OR NEW.created_by != OLD.created_by
                         OR NOT (NEW.published_by IS OLD.published_by)
                         OR NOT (NEW.published_at IS OLD.published_at)
                         OR NOT (NEW.change_summary IS OLD.change_summary)
                         OR NOT (NEW.score IS OLD.score)
                         OR NEW.hard_conflicts_count != OLD.hard_conflicts_count
                         OR NEW.soft_conflicts_count != OLD.soft_conflicts_count
                         OR NEW.lock_version != OLD.lock_version
                     )
                 )
            BEGIN
                SELECT RAISE(ABORT, 'Inmutabilidad violada: Las versiones publicadas solo pueden transicionar a ARCHIVED sin alterar campos estructurales o de datos.');
            END;
        ");
    }

    private function createMysqlTriggers(): void
    {
        DB::unprepared("
            CREATE TRIGGER trg_prevent_published_assignment_insert
            BEFORE INSERT ON schedule_assignments
            FOR EACH ROW
            BEGIN
                DECLARE v_status VARCHAR(20);
                SELECT status INTO v_status FROM schedule_versions WHERE id = NEW.schedule_version_id LIMIT 1;
                IF v_status IN ('PUBLISHED', 'ARCHIVED') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inmutabilidad violada: No se pueden insertar asignaciones en versiones PUBLISHED o ARCHIVED.';
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_prevent_published_assignment_update
            BEFORE UPDATE ON schedule_assignments
            FOR EACH ROW
            BEGIN
                DECLARE old_status VARCHAR(20);
                DECLARE new_status VARCHAR(20);
                SELECT status INTO old_status FROM schedule_versions WHERE id = OLD.schedule_version_id LIMIT 1;
                SELECT status INTO new_status FROM schedule_versions WHERE id = NEW.schedule_version_id LIMIT 1;
                IF old_status IN ('PUBLISHED', 'ARCHIVED') OR new_status IN ('PUBLISHED', 'ARCHIVED') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inmutabilidad violada: No se pueden modificar ni transferir asignaciones en versiones PUBLISHED o ARCHIVED.';
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_prevent_published_assignment_delete
            BEFORE DELETE ON schedule_assignments
            FOR EACH ROW
            BEGIN
                DECLARE v_status VARCHAR(20);
                SELECT status INTO v_status FROM schedule_versions WHERE id = OLD.schedule_version_id LIMIT 1;
                IF v_status IN ('PUBLISHED', 'ARCHIVED') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inmutabilidad violada: No se pueden eliminar asignaciones en versiones PUBLISHED o ARCHIVED.';
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_prevent_published_version_delete
            BEFORE DELETE ON schedule_versions
            FOR EACH ROW
            BEGIN
                IF OLD.status IN ('PUBLISHED', 'ARCHIVED') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inmutabilidad violada: No se pueden eliminar versiones PUBLISHED o ARCHIVED.';
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_prevent_published_version_update
            BEFORE UPDATE ON schedule_versions
            FOR EACH ROW
            BEGIN
                IF OLD.status = 'ARCHIVED' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inmutabilidad violada: Las versiones archivadas son inmutables.';
                END IF;
                IF OLD.status = 'PUBLISHED' THEN
                    IF NEW.status != 'ARCHIVED' 
                       OR NEW.work_period_id != OLD.work_period_id
                       OR NEW.version_number != OLD.version_number
                       OR NOT (NEW.parent_version_id <=> OLD.parent_version_id)
                       OR NEW.created_by != OLD.created_by
                       OR NOT (NEW.published_by <=> OLD.published_by)
                       OR NOT (NEW.published_at <=> OLD.published_at)
                       OR NOT (NEW.change_summary <=> OLD.change_summary)
                       OR NOT (NEW.score <=> OLD.score)
                       OR NEW.hard_conflicts_count != OLD.hard_conflicts_count
                       OR NEW.soft_conflicts_count != OLD.soft_conflicts_count
                       OR NEW.lock_version != OLD.lock_version THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inmutabilidad violada: Las versiones publicadas solo pueden transicionar a ARCHIVED sin alterar campos de datos.';
                    END IF;
                END IF;
            END;
        ");
    }
};
