<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Tools\Notification;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use League\Flysystem\Config;
use Lovexjho\FlysystemTosOss\TosAdapterFactory;
use MessageNotify\Channel\DingTalkChannel;
use MessageNotify\Notify;
use MessageNotify\Template\Text;
use Tos\Model\Enum;
use function Hyperf\Config\generateRandomString;


#[AutoController]
class IndexController extends AbstractController
{

    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
