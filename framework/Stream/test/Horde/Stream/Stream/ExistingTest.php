<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Stream
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Stream_Existing class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Stream
 * @subpackage UnitTests
 */
class Horde_Stream_Stream_ExistingTest extends Horde_Stream_Stream_TestBase
{
    protected function _getOb()
    {
        return new Horde_Stream_Existing(array(
            'stream' => fopen('php://temp', 'r+')
        ));
    }

}
