from pathlib import Path
p=Path('.verify-session-sql.php');s=p.read_text();s=s.replace("$pdo->exec($statement);$buffer='';", "$st=$pdo->query($statement);do{if($st->columnCount())$st->fetchAll();}while($st->nextRowset());$st->closeCursor();$buffer='';")
p.write_text(s)
