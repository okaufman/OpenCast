<?php

declare(strict_types=1);

use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\UI\PaellaConfig\PaellaConfigFormBuilder;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class xoctConfGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctConfGUI : xoctMainGUI
 */
class xoctConfGUI extends xoctGUI
{
    use TranslatorTrait;
    use LocaleTrait;

    public const CMD_PLAYER = 'player';
    public const CMD_UPDATE_PLAYER = 'updatePlayer';

    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var UploadHandler
     */
    private $fileUploadHandler;
    /**
     * @var PaellaConfigFormBuilder
     */
    private $paellConfigFormBuilder;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;

    public function __construct(
        Renderer $renderer,
        UploadHandler $fileUploadHandler,
        PaellaConfigFormBuilder $paellConfigFormBuilder
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->renderer = $renderer;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->paellConfigFormBuilder = $paellConfigFormBuilder;
    }

    public function executeCommand(): void
    {
        $nextClass = $this->ctrl->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctFileUploadHandlerGUI::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString("msg_no_access"), true);
                    $this->cancel();
                }
                $this->ctrl->forwardCommand($this->fileUploadHandler);
                break;
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }
    }


    /**
     *
     */
    protected function index(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->fillForm();
        $this->main_tpl->setContent($xoctConfFormGUI->getHTML());
    }

    /**
     * Subtab Player has an own method, since it is rendered with the UI service and not with xoctConfFormGUI
     * @return void
     */
    protected function player(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER));
        $this->main_tpl->setContent($this->renderer->render($form));
    }

    protected function updatePlayer(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER))
                                             ->withRequest($this->http->request());
        $data = $form->getData();
        if (!$data) {
            $this->main_tpl->setContent($this->renderer->render($form));
            return;
        }

        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION])) {
            $paella_player_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][0];
            PluginConfig::set(PluginConfig::F_PAELLA_OPTION, $paella_player_option);
            if ($paella_player_option === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(
                    PluginConfig::F_PAELLA_URL,
                    $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][1]['url']
                );
            }
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_THEME])) {
            $paella_player_theme = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_THEME][0];
            PluginConfig::set(PluginConfig::F_PAELLA_THEME, $paella_player_theme);
            if ($paella_player_theme === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(
                    PluginConfig::F_PAELLA_THEME_URL,
                    $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_THEME][1]['url']
                );
            }
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_THEME])) {
            $paella_player_live_theme = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_THEME][0];
            PluginConfig::set(PluginConfig::F_PAELLA_THEME_LIVE, $paella_player_live_theme);
            if ($paella_player_live_theme === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(
                    PluginConfig::F_PAELLA_THEME_URL_LIVE,
                    $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_THEME][1]['url']
                );
            }
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_FALLBACK])) {
            $paella_player_preview_fallback = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_FALLBACK][0];
            PluginConfig::set(PluginConfig::F_PAELLA_PREVIEW_FALLBACK, $paella_player_preview_fallback);
            if ($paella_player_preview_fallback === PluginConfig::PAELLA_OPTION_URL) {
                PluginConfig::set(
                    PluginConfig::F_PAELLA_PREVIEW_FALLBACK_URL,
                    $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_FALLBACK][1]['url']
                );
            }
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION])) {
            $paella_fallback_captions_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION];
            PluginConfig::set(PluginConfig::F_PAELLA_FALLBACK_CAPTIONS, $paella_fallback_captions_option);
        }
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION])) {
            $paella_fallback_langs_option = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION];
            PluginConfig::set(PluginConfig::F_PAELLA_FALLBACK_LANGS, $paella_fallback_langs_option);
        }

        $this->ctrl->redirect($this, self::CMD_PLAYER);
    }

    /**
     *
     */
    protected function update(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->setValuesByPost();
        if ($xoctConfFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->main_tpl->setContent($xoctConfFormGUI->getHTML());
    }

    /**
     *
     */
    protected function confirmDelete(): void
    {
    }

    /**
     *
     */
    protected function delete(): void
    {
    }

    /**
     *
     */
    protected function add(): void
    {
    }

    /**
     *
     */
    protected function create(): void
    {
    }

    /**
     *
     */
    protected function edit(): void
    {
    }
}
