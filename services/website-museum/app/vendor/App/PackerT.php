<?php

trait App_PackerT
{
    protected function packLog(array $fields)
    {
        return $this->pack($fields, [
            "id",
            "timestamp",
            "message"
        ]);
    }

    protected function pack(array $row, array $fields)
    {
        $more = [];

        foreach ($row as $k => $v) {
            if ($v === "")
                $v = null;

            if (!in_array($k, $fields)) {
                $more[$k] = $v;
                unset($row[$k]);
            }
        }

        $row["more"] = $more ? serialize($more) : null;

        return $row;
    }

    protected function unpack(array $row)
    {
        if (array_key_exists("more", $row)) {
            if ($row["more"][0] == '{')
                $more = json_decode($row["more"], true);
            else
                $more = unserialize($row["more"]);
            unset($row["more"]);
            if (is_array($more))
                $row = array_merge($row, $more);
        }

        return $row;
    }
}
