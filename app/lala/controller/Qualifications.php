<?php
declare (strict_types = 1);

namespace app\lala\controller;

use think\admin\Controller;

/**
 * 企业资质控制器
 */
class Qualifications extends Controller
{
    /**
     * 企业资质展示页面
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '企业资质证书';
        // 企业资质数据
        $qualifications = [
            'business_license' => [
                'name' => '营业执照',
                'image' => '/image/qualification/营业执照.jpg',
                'description' => '重庆鸿枢慧科技有限公司营业执照',
                'type' => 'license'
            ],
            'idc_certificates' => [
                'name' => 'IDC相关证书',
                'certificates' => [
                    [
                        'name' => 'ICP许可证',
                        'image' => '/image/qualification/EDI.jpg',
                        'description' => '增值电信业务经营许可证 - ICP'
                    ],
                    [
                        'name' => 'EDI许可证',
                        'image' => '/image/qualification/ICP.jpg',
                        'description' => '增值电信业务经营许可证 - EDI'
                    ],
                    [
                        'name' => 'IDC许可证',
                        'image' => '/image/qualification/IDC+CDN+ISP+VPN+固定网.jpg',
                        'description' => '增值电信业务经营许可证 - IDC'
                    ],
                    [
                        'name' => 'CDN许可证',
                        'image' => '/image/qualification/IDC+CDN+ISP+VPN+固定网.jpg',
                        'description' => '增值电信业务经营许可证 - CDN'
                    ],
                    [
                        'name' => 'ISP许可证',
                        'image' => '/image/qualification/IDC+CDN+ISP+VPN+固定网.jpg',
                        'description' => '增值电信业务经营许可证 - ISP'
                    ],
                    [
                        'name' => 'VPN许可证',
                        'image' => '/image/qualification/IDC+CDN+ISP+VPN+固定网.jpg',
                        'description' => '增值电信业务经营许可证 - VPN'
                    ],
                    [
                        'name' => '固定网国内数据传送业务许可证',
                        'image' => '/image/qualification/IDC+CDN+ISP+VPN+固定网.jpg',
                        'description' => '固定网国内数据传送业务经营许可证'
                    ]
                ],
                'type' => 'certificates'
            ],
            'domain_filing' => [
                'name' => '域名备案证书',
                'certificates' => [
                    [
                        'name' => '渝ICP备案',
                        'image' => '/image/qualification/域名ICP备案.png',
                        'description' => '域名ICP备案证书'
                    ],
                    [
                        'name' => '渝公网安备案',
                        'image' => '/image/qualification/公网安备案.png',
                        'description' => '域名公安备案证书'
                    ]
                ],
                'type' => 'certificates'
            ],
            'security_assessment' => [
                'name' => '安全评估证书',
                'certificates' => [
                    [
                        'name' => '安全评估',
                        'image' => '/image/qualification/安全评估.png',
                        'description' => '网络安全评估证书'
                    ]
                ],
                'type' => 'certificates'
            ]
        ];
        // 分配变量到视图
        $this->assign([
            'qualifications' => $qualifications
        ]);

        return $this->fetch();
    }
} 