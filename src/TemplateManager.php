<?php

namespace Convelio;

use Convelio\Entity\Quote;
use Convelio\Entity\Template;
use Convelio\Context\ApplicationContext;
use Convelio\Repository\QuoteRepository;
use Convelio\Repository\SiteRepository;
use Convelio\Repository\DestinationRepository;
use Convelio\Entity\User;

class TemplateManager
{
    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    /**
     * @param $text
     * @param array $data
     * @return string
     */
    private function computeText($text, array $data) :string
    {
        $arrayReplace = [];
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();
        $quote = (isset($data['quote']) && $data['quote'] instanceof Quote) ? $data['quote'] : null;
        $user = (isset($data['user']) && ($data['user'] instanceof User)) ? $data['user'] : $APPLICATION_CONTEXT->getCurrentUser();

        /*
          * QUOTE
          * [quote:*]
          */
        if ($quote) {
            $arrayReplace = $this->quotePattern($quote, $arrayReplace);
        }

        /*
         * USER
         * [user:*]
         */
        if ($user) {
            $arrayReplace = $this->userPattern($user, $arrayReplace);
        }

        return $this->replace($arrayReplace, $text);
    }

    /**
     * @param Quote $quote
     * @param array $arrayReplace
     * @return array
     */
    public function quotePattern(Quote $quote, array $arrayReplace) : array
    {
        $_quoteFromRepository   = QuoteRepository::getInstance()->getById($quote->id);
        $usefulObject           = SiteRepository::getInstance()->getById($quote->siteId);
        $destinationOfQuote     = DestinationRepository::getInstance()->getById($quote->destinationId);

        $link = sprintf(
            '%s/%s/quote/%s',
            $usefulObject->url->url,
            $destinationOfQuote->countryName,
            $_quoteFromRepository->id
        );

        return  array_merge($arrayReplace,
                        [
                            '[quote:summary_html]'      => Quote::renderHtml($_quoteFromRepository),
                            '[quote:summary]'           => Quote::renderText($_quoteFromRepository),
                            '[quote:destination_name]'  => $destinationOfQuote->countryName,
                            '[quote:destination_link]'  => $link
                        ]
                );
    }

    /**
     * @param User $user
     * @param array $arrayReplace
     * @return array
     */
    public function userPattern(User $user, array $arrayReplace) : array
    {
        return  array_merge($arrayReplace,
                    [
                    '[user:first_name]' => ucfirst(mb_strtolower($user->firstname)),
                    '[user:last_name]'  => ucfirst(mb_strtolower($user->lastname))
                    ]
                );
    }

    /**
     * @param $replace
     * @param $text
     * @return mixed
     */
    private function replace($replace, $text)
    {
        foreach ($replace as $search => $value) {
            (strpos($text, $search) !== false) && $text = str_replace($search, $value, $text);
        }
        return $text;
    }
}
