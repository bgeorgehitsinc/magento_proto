<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an e-mail
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$search = $argv[1];
$files = shell_exec("find . -name $search");
$xsl = 'dev/tools/xml/logging.xslt';
$saxon = 'dev/tools/xml/saxon9he.jar';

foreach (preg_split("/((\r?\n)|(\r\n?))/", $files) as $file) {
    if (!empty($file)) {
        if (!file_exists($saxon)) {
            $url = 'http://repo1.maven.org/maven2/net/sf/saxon/Saxon-HE/9.5.1-1/Saxon-HE-9.5.1-1.jar';
            system("wget $url --output-document=$saxon");
        }
        $cmd = "java -jar $saxon -l:on -s:$file -xsl:$xsl -o:$file";
        echo "$cmd \n";
        system($cmd);
    }
}