<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Invite_Component_Controller_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        if (!$this->request()->get('user') && !$this->request()->get('id')) {
            Phpfox::isUser(true);
        }

        list($bIsRegistration, $sNextUrl) = $this->url()->isRegistration(2);

        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_start')) ? eval($sPlugin) : false);

        // is a user sending an invite
        if ($aVals = $this->request()->getArray('val')) {
            // we may have a bunch of emails separated by commas, lets array them
            $aMails = explode(',', $aVals['emails']);

            list($aMails, $aInvalid, $aCacheUsers) = Phpfox::getService('invite')->getValid($aMails,
                Phpfox::getUserId());

            // failed emails
            if (!empty($aMails)) {
                foreach ($aMails as $sMail) {
                    $sMail = trim($sMail);
                    // we insert the invite id and send the reference, so we can track which users
                    // have signed up

                    $iInvite = Phpfox::getService('invite.process')->addInvite($sMail, Phpfox::getUserId());

                    (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_send')) ? eval($sPlugin) : false);

                    $sFromEmail = Phpfox::getParam('core.email_from_email');
                    // check if we could send the mail
                    $sLink = Phpfox_Url::instance()->makeUrl('invite', array('id' => $iInvite));
                    Phpfox_Mail::instance()->to($sMail)
                        ->fromEmail((empty($sFromEmail) ? Phpfox::getUserBy('email') : Phpfox::getParam('core.email_from_email')))
                        ->fromName(Phpfox::getUserBy('full_name'))
                        ->subject(array(
                            'invite.full_name_invites_you_to_site_title',
                            array(
                                'full_name' => Phpfox::getUserBy('full_name'),
                                'site_title' => Phpfox::getParam('core.site_title')
                            )
                        ))
                        ->message(array(
                            'invite.full_name_invites_you_to_site_title_link',
                            array(
                                'full_name' => Phpfox::getUserBy('full_name'),
                                'site_title' => Phpfox::getParam('core.site_title'),
                                'link' => $sLink
                            )
                        ))
                        ->send();
                }
            }

            if ($bIsRegistration === true) {
                $this->url()->send($sNextUrl, null, _p('your_friends_have_successfully_been_invited'));
            }

            $this->template()->assign(array(
                    'aValid' => $aMails,
                    'aInValid' => $aInvalid,
                    'aUsers' => $aCacheUsers
                )
            );
        }

        // check if someone is visiting a link sent by email
        if (($iId = $this->request()->getInt('id'))) {
            if (Phpfox::isUser() == true) {
                $this->url()->send('core.index-member');
            }
            // we update the entry to be seen:
            if (Phpfox::getService('invite.process')->updateInvite($iId, true)) {
                $this->url()->send('user.register');
            } else {
                Phpfox_Error::set(_p('your_invitation_has_expired_or_it_was_not_valid'));

                return Phpfox_Module::instance()->setController('core.index-visitor');
            }
        } // check if someone is visiting from a link pasted in a site or other places
        elseif ($iId = $this->request()->getInt('user')) {
            if (Phpfox::getService('invite.process')->updateInvite($iId, false)) {
                $this->url()->send('user.register');
            }
        }

        $this->template()->setTitle(_p('invite_your_friends'))
            ->setBreadCrumb(_p('invite_your_friends'))
            ->assign(array(
                    'sFullName' => Phpfox::getUserBy('full_name'),
                    'sSiteEmail' => Phpfox::getUserBy('email'),
                    'sSiteTitle' => Phpfox::getParam('core.site_title'),
                    'sIniviteLink' => Phpfox_Url::instance()->makeUrl('invite', array('user' => Phpfox::getUserId())),
                    'bIsRegistration' => $bIsRegistration,
                    'sNextUrl' => $this->url()->makeUrl($sNextUrl)
                )
            )->buildSectionMenu('invite', [
                _p('invite_friends') => '',
                _p('pending_invitations') => 'invite.invitations'
            ]);

        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_end')) ? eval($sPlugin) : false);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_clean')) ? eval($sPlugin) : false);
    }
}