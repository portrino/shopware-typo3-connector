# Shopware TYPO3-Connector
[![Latest Stable Version](https://poser.pugx.org/portrino/shopware-typo3-connector/v/stable)](https://packagist.org/packages/portrino/shopware-typo3-connector)
[![Total Downloads](https://poser.pugx.org/portrino/shopware-typo3-connector/downloads)](https://packagist.org/packages/portrino/shopware-typo3-connector)

## Description

Shows shopware articles and categories with TYPO3. Requires the TYPO3 extension [px_shopware](https://github.com/portrino/px_shopware). 
The plugin enables the connection of TYPO3 and the Shopware API. The TYPO3 extension offers several frontend plugins to show Shopware data like articles, categories, etc. as lists or detail views within the TYPO3 frontend.
Additionally, we provide the feature to index articles, categories etc. via TYPO3 with the help of the EXT:Solr. If changes are made on articles or categories within shopware we notify the TYPO3 CMS to invalidate e.g. page 
caches which are related to the articles. We also notify TYPO3 to reindex the product if some information are changed to get up-to-date information in your search results.

## Installation

The Shopware plugin can be installed within the plugin manager or via composer.

Additionally, you need to install and configure the extension "px_shopware" within TYPO3. 
It can be imported from TER (TYPO3 Extension Repository): https://typo3.org/extensions/repository/view/px_shopware or better you get it from 
packagist: https://packagist.org/packages/portrino/px_shopware

## Authors

* **André Wuttig** - *Initial work* - [aWuttig](https://github.com/aWuttig)
* **Thomas Griessbach** - *Solr Integration* - [tgriessbach](https://github.com/tgriessbach)
* **Andreas Haubold** - *Documentation* - [ahaubold](https://github.com/ahaubold)

See also the list of [contributors](https://github.com/portrino/shopware-typo3-connector/graphs/contributors) who participated in this project.
