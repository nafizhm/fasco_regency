from pathlib import Path
p=Path('.generate-session-sql.php');s=p.read_text().replace('PREPARE nh_stmt FROM @nh_ddl; EXECUTE nh_stmt; DEALLOCATE PREPARE nh_stmt;', 'PREPARE nh_stmt FROM @nh_ddl;\nEXECUTE nh_stmt;\nDEALLOCATE PREPARE nh_stmt;');p.write_text(s)
