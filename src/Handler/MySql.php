<?php
namespace Panadas\SessionManager\Handler;

class MySql extends AbstractPdo
{

    public function read($id)
    {
        $stmt = $this->getPdo()->prepare(
            "
                SELECT `data`
                FROM `{$this->getTableName()}`
                WHERE `id` = :id
            "
        );

        $stmt->bindValue(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return "";
        }

        return base64_decode($stmt->fetchColumn());
    }

    public function write($id, $data)
    {
        $stmt = $this->getPdo()->prepare(
            "
                INSERT INTO `{$this->getTableName()}` (
                    `id`,
                    `data`,
                    `created`,
                    `modified`
                )
                VALUES (
                    :id,
                    :data,
                    :created,
                    `created`
                )
                ON DUPLICATE KEY UPDATE
                    `data` = VALUES(`data`),
                    `modified` = VALUES(`created`)
            "
        );

        $stmt->bindValue(":id", $id);
        $stmt->bindValue(":data", base64_encode($data));
        $stmt->bindValue(":created", (new \DateTime())->format("Y-m-d H:i:s"));
        $stmt->execute();

        return true;
    }

    public function destroy($id)
    {
        $stmt = $this->getPdo()->prepare(
            "
                DELETE FROM `{$this->getTableName()}`
                WHERE `id` = :id
            "
        );

        $stmt->bindValue(":id", $id);
        $stmt->execute();

        return true;
    }

    public function gc($lifetime)
    {
        $stmt = $this->getPdo()->prepare(
            "
                DELETE FROM `{$this->getTableName()}`
                WHERE `modified` <= :modified
            "
        );

        $stmt->bindValue(":modified", (new \DateTime("-{$lifetime} seconds"))->format("Y-m-d H:i:s"));
        $stmt->execute();

        return true;
    }
}
