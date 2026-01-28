<?php
/**
 * Ошибка на случай нарушения уникальности.
 *
 * MySQL: 1062 (Duplicate entry 'foo' for key bar).
 **/

class Framework_Errors_Duplicate extends RuntimeException
{
}
