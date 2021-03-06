<?php
/**
 * Copyright 2005-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Message viewing action for AJAX application handler.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_ShowMessage
{
    /**
     * Default list of part info elements to display.
     *
     * @var array
     */
    public $part_info = array(
        'icon', 'description', 'size', 'download'
    );

    /**
     * Contents object.
     *
     * @var IMP_Contents
     */
    protected $_contents;

    /**
     * Envelope object.
     *
     * @var Horde_Imap_Client_Data_Envelope
     */
    protected $_envelope;

    /**
     * Indices object.
     *
     * @var IMP_Indices
     */
    protected $_indices;

    /**
     * Don't seen seen flag?
     *
     * @var boolean
     */
    protected $_peek;

    /**
     * Constructor.
     *
     * @param IMP_Indices $indices  The index of the message.
     * @param boolean $peek         Don't set seen flag?
     */
    public function __construct(IMP_Indices $indices, $peek = false)
    {
        global $injector;

        /* Get envelope/header information. We don't use flags in this
         * view. */
        try {
            list($mbox, $uid) = $indices->getSingle();
            if (!$uid) {
                throw new Exception();
            }

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();

            $imp_imap = $mbox->imp_imap;
            $imp_imap->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);

            $ret = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));

            if (!($ob = $ret->first())) {
                throw new Exception();
            }

            $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create($indices);
        } catch (Exception $e) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $this->_contents = $imp_contents;
        $this->_envelope = $ob->getEnvelope();
        $this->_indices = $indices;
        $this->_peek = $peek;
    }

    /**
     * Create the object used to display the message.
     *
     * @param array $args  Configuration parameters:
     *   - headers: (array) The headers desired in the returned headers array
     *              (only used with non-preview view).
     *   - preview: (boolean) Is this the preview view?
     *
     * @return array  Array with the following keys:
     *   - atc: (object) Attachment information.
     *     - download: (string) The URL for the download all action.
     *     - label: (string) The attachment label.
     *     - list: (array) Attachment information.
     *   - bcc: (array; FULL) The Bcc addresses.
     *   - cc: (array) The CC addresses.
     *   - datestamp: (string) ISO 8601 date string.
     *   - fulldate: (string; FULL) The full canonical date.
     *   - from: (array) The From addresses.
     *   - headers: (array; FULL): An array of header information (not
     *              including basic headers).
     *   - js: (array) Javascript code to run on display.
     *   - list_info: (array; FULL) List information.
     *   - localdate: (string; PREVIEW) The date formatted to the user's
     *                timezone.
     *   - md: (array) Metadata.
     *   - msgtext: (string) The text of the message.
     *   - onepart: (boolean) True if message only contains one part.
     *   - replyTo: (array; FULL) The Reply-to addresses.
     *   - save_as: (string) The save link.
     *   - subject: (string) The subject.
     *   - subjectlink: (string) The subject with linked URLs/email addresses
     *                  (defaults to 'subject')
     *   - title: (string; FULL) The title of the page.
     *   - to: (array) The To addresses.
     *
     * @throws IMP_Exception
     */
    public function showMessage($args)
    {
        global $injector, $page_output, $prefs, $registry, $session;

        $preview = !empty($args['preview']);
        $result = array();

        $mime_headers = $this->_peek
            ? $this->_contents->getHeader()
            : $this->_contents->getHeaderAndMarkAsSeen();

        $headers = array();
        $imp_ui = $injector->getInstance('IMP_Message_Ui');

        /* Develop the list of Headers to display now. Deal with the 'basic'
         * header information first since there are various manipulations
         * done to them. */
        $basic_headers = $imp_ui->basicHeaders();
        if (empty($args['headers'])) {
            $args['headers'] = array('from', 'date', 'to', 'cc', 'bcc');
        }

        $headers_list = array_intersect_key($basic_headers, array_flip($args['headers']));

        /* Build From/To/Cc/Bcc/Reply-To links. */
        foreach (array('from', 'to', 'cc', 'bcc', 'reply-to') as $val) {
            if (isset($headers_list[$val]) &&
                (!$preview || ($val != 'reply-to'))) {
                if ($tmp = $this->getAddressHeader($val)) {
                    $result[$val] = $tmp;
                }
                if ($preview) {
                    unset($headers_list[$val]);
                }
            }
        }

        /* Build the rest of the headers. */
        foreach ($headers_list as $head => $str) {
            if ($val = $mime_headers[$head]) {
                if ($head == 'date') {
                    /* Add local time to date header. */
                    $date_ob = new IMP_Message_Date($this->_envelope->date);
                    $val = $date_ob->format($date_ob::DATE_LOCAL);
                    if ($preview) {
                        $result['localdate'] = $val;
                    } else {
                        $result['fulldate'] = $date_ob->format($date_ob::DATE_FORCE);
                    }
                    $result['datestamp'] = $date_ob->format($date_ob::DATE_ISO_8601);
                }

                if (!$preview) {
                    $headers[$head] = array(
                        'id' => Horde_String::ucfirst($head),
                        'name' => $str,
                        'value' => $val
                    );
                }
            }
        }

        if (empty($result['reply-to']) ||
            ($result['from']['addr'][0]->b == $result['reply-to']['addr'][0]->b)) {
            unset($result['reply-to'], $headers['reply-to']);
        }

        /* JS requires camelized name for reply-to. */
        if (!$preview && isset($headers['reply-to'])) {
            $result['replyTo'] = $result['reply-to'];
            $headers['reply-to']['id'] = 'ReplyTo';
            unset($result['reply-to']);
        }

        /* Maillog information. */
        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $ajax_queue->maillog($this->_indices);

        if (!$preview) {
            /* Display the user-specified headers for the current identity. */
            $user_hdrs = $imp_ui->getUserHeaders();
            foreach ($user_hdrs as $user_hdr) {
                if ($user_val = $mime_headers[$user_hdr]) {
                    $user_val = $user_val->value;
                    foreach ((is_array($user_val) ? $user_val : array($user_val)) as $val) {
                        $headers[] = array(
                            'name' => $user_hdr,
                            'value' => $val
                        );
                    }
                }
            }
            $result['headers'] = array_values($headers);
        }

        /* Process the subject. */
        if ($subject = strval($mime_headers['Subject'])) {
            $text_filter = $injector->getInstance('Horde_Core_Factory_TextFilter');
            $filtered_subject = preg_replace("/\b\s+\b/", ' ', IMP::filterText($subject));

            $result['subject'] = $text_filter->filter($filtered_subject, 'text2html', array(
                'parselevel' => Horde_Text_Filter_Text2html::NOHTML
            ));
            $subjectlink = $text_filter->filter($filtered_subject, 'text2html', array(
                'parselevel' => Horde_Text_Filter_Text2html::MICRO
            ));

            if ($subjectlink != $result['subject']) {
                $result['subjectlink'] = $subjectlink;
            }
            if (!$preview) {
                $result['title'] = $subject;
            }
        } else {
            $result['subject'] = _("[No Subject]");
            if (!$preview) {
                $result['title'] = _("[No Subject]");
            }
        }

        // Create message text and attachment list.
        $result['msgtext'] = '';
        $part_info = $this->part_info;
        $show_parts = $prefs->getValue('parts_display');

        list($mbox, $uid) = $this->_indices->getSingle();

        /* Do MDN processing now. */
        switch ($registry->getView()) {
        case $registry::VIEW_DYNAMIC:
            if ($this->_indices->mdnCheck($mime_headers)) {
                $status = new IMP_Mime_Status(null, array(
                    _("The sender of this message is requesting notification from you when you have read this message."),
                    Horde::link('#', '', '', '', '', '', '', array('id' => 'send_mdn_link')) . _("Click to send the notification message.") . '</a>'
                ));
                $status->domid('sendMdnMessage');
                $result['msgtext'] .= strval($status);
            }
        }

        /* Build body text. This needs to be done before we build the
         * attachment list. */
        $session->close();
        $inlineout = $this->getInlineOutput();
        $session->start();

        $result['md'] = $inlineout['metadata'];
        $result['msgtext'] .= $inlineout['msgtext'];
        if ($inlineout['one_part']) {
            $result['onepart'] = true;
        }

        if (count($inlineout['atc_parts']) ||
            (($show_parts == 'all') && count($inlineout['display_ids']) > 2)) {
            $result['atc']['label'] = ($show_parts == 'all')
                ? _("Parts")
                : sprintf(ngettext("%d Attachment", "%d Attachments", count($inlineout['atc_parts'])), count($inlineout['atc_parts']));
            if (count($inlineout['atc_parts']) > 1) {
                $result['atc']['download'] = strval($this->_contents->urlView(
                    $this->_contents->getMIMEMessage(),
                    'download_all'
                )->setRaw(true));
            }
        }

        /* Show attachment information in headers? */
        if (!empty($inlineout['atc_parts'])) {
            $partlist = array();

            if ($show_parts == 'all') {
                array_unshift($part_info, 'id');
            }

            foreach ($inlineout['atc_parts'] as $id) {
                $contents_mask = IMP_Contents::SUMMARY_DESCRIP |
                    IMP_Contents::SUMMARY_DESCRIP_LINK |
                    IMP_Contents::SUMMARY_DOWNLOAD |
                    IMP_Contents::SUMMARY_ICON |
                    IMP_Contents::SUMMARY_SIZE;
                $part_info[] = 'description_raw';
                $part_info[] = 'download_url';

                $summary = $this->_contents->getSummary($id, $contents_mask);
                $tmp = array();
                foreach ($part_info as $val) {
                    if (isset($summary[$val])) {
                        $tmp[$val] = ($summary[$val] instanceof Horde_Url)
                            ? strval($summary[$val]->setRaw(true))
                            : $summary[$val];
                    }
                }
                $partlist[] = array_filter($tmp);
            }

            $result['atc']['list'] = $partlist;
        }

        list($bmbox, $buid) = ($this->_indices instanceof IMP_Indices_Mailbox)
            ? $this->_indices->buids->getSingle()
            : $this->_indices->getSingle();

        $result['save_as'] = IMP_Contents_View::downloadUrl(
            htmlspecialchars_decode($result['subject']),
            array_merge(
                array('actionID' => 'save_message'),
                $bmbox->urlParams($buid)
            )
        );

        if ($preview) {
            /* Need to grab cached inline scripts. */
            Horde::startBuffer();
            $page_output->outputInlineScript(true);
            if ($js_inline = Horde::endBuffer()) {
                $result['js'] = array($js_inline);
            }

            $result['save_as'] = strval($result['save_as']->setRaw(true));
        } else {
            $list_info = $imp_ui->getListInformation($mime_headers);
            if (!empty($list_info['exists'])) {
                $result['list_info'] = $list_info;
            }
        }

        /* Add changed flag information. */
        if (!$this->_peek && $mbox->is_imap) {
            $status = $mbox->imp_imap->status($mbox, Horde_Imap_Client::STATUS_PERMFLAGS);
            if (in_array(Horde_Imap_Client::FLAG_SEEN, $status['permflags'])) {
                $ajax_queue->flag(array(Horde_Imap_Client::FLAG_SEEN), true, $this->_indices);
            }
        }

        return $result;
    }

    /**
     * Return data to build an address header.
     *
     * @param string $header  The address header.
     * @param integer $limit  Limit display to this many addresses. If null,
     *                        shows all addresses.
     *
     * @return array  An array with the following entries:
     *   - addr: (array) List of addresses/groups.
     *           Group keys: 'a' (list of addresses); 'g' (group name)
     *           Address keys: 'b' (bare address); 'p' (personal part)
     *   - limit: (integer) If limit was reached, the number of total
     *            addresses.
     *   - raw: (string) A raw string to display instead of addresses.
     */
    public function getAddressHeader($header, $limit = 50)
    {
        $addrlist = ($header == 'reply-to')
            ? $this->_envelope->reply_to
            : $this->_envelope->$header;
        $addrlist->unique();

        $addr_ob = new IMP_Ajax_Addresses($addrlist);
        $addr_array = $addr_ob->toArray($limit);

        $out = array();
        if ($addr_array->limit) {
            $out['limit'] = $addr_array->total;
        }

        if (!empty($addr_array->addr)) {
            $out['addr'] = $addr_array->addr;
        } elseif ($header == 'to') {
            $out['raw'] = _("Undisclosed Recipients");
        }

        return $out;
    }

    /**
     * Get the inline display output for a message.
     *
     * @param string $mimeid  Restrict output to this MIME ID (and children).
     *
     * @return array  See IMP_Contents_InlineOutput#getInlineOutput().
     */
    public function getInlineOutput($mimeid = null)
    {
        global $registry;

        switch ($registry->getView()) {
        case $registry::VIEW_MINIMAL:
        case $registry::VIEW_SMARTMOBILE:
            $contents_mask = 0;
            break;

        default:
            $contents_mask = IMP_Contents::SUMMARY_BYTES |
                IMP_Contents::SUMMARY_SIZE |
                IMP_Contents::SUMMARY_ICON |
                IMP_Contents::SUMMARY_DESCRIP_LINK |
                IMP_Contents::SUMMARY_DOWNLOAD |
                IMP_Contents::SUMMARY_PRINT_STUB;
            break;
        }

        $part_info = $part_info_display = $this->part_info;
        $part_info_display[] = 'print';

        $inline_ob = new IMP_Contents_InlineOutput();
        return $inline_ob->getInlineOutput($this->_contents, array(
            'mask' => $contents_mask,
            'mimeid' => $mimeid,
            'part_info_display' => $part_info_display
        ));
    }

}
