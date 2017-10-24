<?php

namespace Perevorot\Rialtotender\Traits;

trait AccessToTenders
{
    use CurrentLocale;

    protected $redirectPage = 'welcome';

    /**
     * @return bool
     */
    protected function redirectTo()
    {
        $this->redirectPage = $this->getCurrentLocale() . $this->redirectPage;

        if(!(boolean) env('USER_SEARCH_ACCESS'))
        {
            return redirect()->to($this->redirectPage);
        }

        return false;
    }
}
