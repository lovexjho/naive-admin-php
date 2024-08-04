<?php

namespace App\Controller\Oss;

use App\Controller\AbstractController;
use App\Middleware\PermissionMiddleware;
use App\Model\Upfile;
use App\Services\UpfileService;
use App\Services\UserService;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use League\Flysystem\Config;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Qbhy\HyperfAuth\AuthMiddleware;
use Tos\Model\Enum;
use function Hyperf\Support\env;

/**
 * 火山存储控制器
 * @package App\Controller\Oss
 * @class TosController
 * @author lovexjho 2024-08-03
 */
#[Controller]
#[Middlewares([AuthMiddleware::class,PermissionMiddleware::class])]
class TosController extends AbstractController
{
    #[Inject]
    public ConfigInterface $config;
    #[Inject]
    public FilesystemFactory $factory;

    #[Inject]
    public UpfileService $upfileService;

    #[Inject]
    public ValidatorFactoryInterface $validatorFactory;

    /**
     * 分页
     * @return \Psr\Http\Message\ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('list')]
    public function list()
    {
        $page = $this->request->query('pageNo');
        $page = max($page, 1);
        $pageSize = $this->request->query('pageSize', 10);
        $name = $this->request->query('name');
        $mime = $this->request->query('mime');
        $visible = $this->request->query('visible', '');

        $conditions = [];

        if ($name) {
            $conditions[] = ['client_name', 'like', "%$name%"];
        }

        if ($mime) {
            $conditions[] = ['mime', 'like', "%$mime%"];
        }

        if (strlen($visible)) {
            $conditions[] = ['visible', $visible];
        }

        $paginate = $this->upfileService->paginate($conditions, $page, $pageSize);

        return $this->success($paginate);
    }

    /**
     * 预签名获取
     * @param FilesystemFactory $factory
     * @param ConfigInterface $config
     * @return \Psr\Http\Message\ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('preSignedURL')]
    public function preSignedURL(FilesystemFactory $factory, ConfigInterface $config)
    {
        $key = $this->request->query('filename');

        if (empty($key)) {
            return $this->error([], 600, '文件名不能为空');
        }

        $filesystem = $factory->getAdapter($config->get('file'), 'tos');
        $sign = $filesystem->preSignedURL($key, new Config([
            'httpMethod' => Enum::HttpMethodGet,
            'cname' => env('TOS_CNAME')
        ]));

        return $this->success($sign);
    }

    /**
     * 上传接口
     * @return \Psr\Http\Message\ResponseInterface
     * @throws FilesystemException
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('upload')]
    public function upload()
    {
        $validator = $this->validatorFactory->make($this->request->all() + ['file' => $this->request->file('file')], [
            'file' => 'bail|required|file|max:10240',
            'visible' => 'required|boolean'
        ]);

        $data = $validator->validated();
        /** @var UploadedFile $file */
        $file = $data['file'];
        $filesystem = $this->factory->get('tos');

        $clientName = $file->getClientFilename();

        if ($this->upfileService->findByName($clientName)) {
            return $this->error([], 600, '该文件已存在');
        }

        $saveName = substr(hash('sha256', $clientName), 0, 6) . '_' . $clientName;

        if ($filesystem->fileExists($saveName)) {
            return $this->error([], 600, '文件名已存在');
        }

        $visible = match ($data['visible']) {
            '1' => Visibility::PUBLIC,
            '0' => Visibility::PRIVATE
        };

        $fileStream = fopen($file->getPathname(), 'rb');
        $filesystem->writeStream($saveName, $fileStream, ['visibility' => $visible]);

        $upfileRes = $this->upfileService->create([
            'client_name' => $clientName,
            'path' => $saveName,
            'mime' => $file->getClientMediaType(),
            'size' => $file->getSize(),
            'visible' => $data['visible']
        ]);

        if (!$upfileRes) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 设置可见
     * @return \Psr\Http\Message\ResponseInterface
     * @throws FilesystemException
     * @user lovexjho 2024-08-03
     */
    #[PostMapping('visible')]
    public function visible()
    {
        $validator = $this->validatorFactory->make($this->request->all(), [
            'id' => 'required|exists:upfile',
            'visible' => 'required|boolean'
        ]);

        $data = $validator->validated();

        /** @var Upfile|bool $upfile */
        if (!$upfile = $this->upfileService->findById($data['id'])) {
            return $this->error();
        }

        $update = $this->upfileService->updateById($data['id'], ['visible' => !$upfile->visible]);

        if (!$update) {
            return $this->error();
        }

        $filesystem = $this->factory->get('tos');
        $filesystem->setVisibility(
            $upfile->getAttributes()['path'],
            $upfile->visible ? Visibility::PRIVATE : Visibility::PUBLIC);

        return $this->success();
    }

    /**
     * 删除文件
     * @param $id
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     * @user lovexjho 2024-08-03
     */
    #[DeleteMapping('{id:\d+}')]
    public function delete($id)
    {
        $validator = $this->validatorFactory->make(['id' => $id], [
            'id' => 'bail|required',
        ]);

        $data = $validator->validated();

        $upfile = $this->upfileService->findById($data['id']);

        if (!$upfile) {
            return $this->error();
        }

        $deleteRes = $this->upfileService->deleteById($data['id']);

        if (!$deleteRes) {
            return $this->error();
        }

        $filesystem = $this->factory->get('tos');

        try {
            $filesystem->delete($upfile->getAttributes()['path']);
        } catch (\Throwable $e) {

        }

        return $this->success();
    }

    /**
     * 生成分享链接，如果是私有，生成签名
     * @param $id
     * @return \Psr\Http\Message\ResponseInterface
     * @user lovexjho 2024-08-03
     */
    #[GetMapping('share/{id:\d+}')]
    public function share($id)
    {
        $upfile = $this->upfileService->findById($id);

        if (!$upfile) {
            return $this->error();
        }

        if ($upfile->visible) {
            $sign = ['signUrl' => $upfile->path];
        } else {
            $filesystem = $this->factory->getAdapter($this->config->get('file'), 'tos');

            $sign = $filesystem->preSignedURL($upfile->getAttributes()['path'], new Config([
                'cname' => env('TOS_CNAME'),
                'expires' => 60
            ]));
        }

        return $this->success($sign);
    }
}