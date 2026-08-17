
/**
 * 光影视界 - 前端逻辑核心
 * 包含模拟数据、路由控制、DOM操作及HLS/MP4/WebM播放器逻辑
 */

const app = {
    // 状态管理
    state: {
        currentPage: 1,
        itemsPerPage: 10,
        currentFilter: 'all', // all, movie, tv
        searchQuery: '',
        sortBy: 'newest',
        data: [],
        currentPlayingItem: null,
        currentEpisode: 1,
        currentHeroId: null,
        hlsInstance: null // 用于存储HLS实例以便销毁
    },

  // 模拟数据库
    mockData:[
        {
            id: 1,
            title: "花开锦绣",
            type: "tv",
            year: 2026,
            rating: 9.3,
            views: 1200500,
            poster: "https://img.feifeiimg.vip/upload/vod/20260809-1/09e614fbd5e4d129d5fbb4cb8651f097.jpg",
            backdrop: "https://picsum.photos/800/400?random=1",
            plot: "豪爽重情的私盐贩子赵凌虽出身草莽，却心怀壮志，他结识了遭人诬陷私通的世家名媛小姐傅庭芸，被迫一起逃亡，二人历经家族与朝廷的重重考验，与命运抗争，终成传奇良缘。剧集改编自吱吱同名言情小说《花开锦绣》。",
            cast: "丁禹兮,邓恩熙,尤靖茹,白澍,吕晓霖,张萌,迟蓬,范诗然,范湉湉,温峥嵘,董子凡,谭凯",
            episodes: 15,
            episodeUrls: {
                1: "https://super.ffzy-online6.com/20260809/38382_1786aee5/index.m3u8",
2: "https://super.ffzy-online6.com/20260809/38383_60c6413e/index.m3u8",
3: "https://super.ffzy-online6.com/20260809/38384_5e2967fb/index.m3u8",
4: "https://super.ffzy-online6.com/20260809/38397_22fa300c/index.m3u8",
5: "https://super.ffzy-online6.com/20260810/38472_79062843/index.m3u8",
6: "https://super.ffzy-online6.com/20260810/38487_522bfd1b/index.m3u8",
7: "https://super.ffzy-online6.com/20260811/38554_2f1227c5/index.m3u8",
8: "https://super.ffzy-online6.com/20260811/38566_8525d6d5/index.m3u8",
9: "https://super.ffzy-online6.com/20260812/38630_51e99940/index.m3u8",
10: "https://super.ffzy-online6.com/20260813/38701_8d6f8b44/index.m3u8",
11: "https://super.ffzy-online6.com/20260813/38702_f4731338/index.m3u8",
12: "https://super.ffzy-online6.com/20260813/38716_5a462570/index.m3u8",
13: "https://super.ffzy-online6.com/20260814/38775_cf7065dd/index.m3u8",
14: "https://super.ffzy-online6.com/20260815/38862_3d86b21e/index.m3u8",
15: "https://super.ffzy-online6.com/20260815/38863_552fd299/index.m3u8"
            } 
        },
        {
            id: 2,
            title: "满清十大酷刑之赤裸凌迟",
            type: "movie",
            year: "1998",
            rating: 9.5,
            views: 980000,
            poster: "https://img.feifeiimg.vip/upload/vod/20250904-1/fa32c071fb7164cd32565f692f063838.jpg",
            backdrop: "https://picsum.photos/800/400?random=2",
            plot: "在马新贻(郑浩南)的祭台下，赤裸的凶手黄莲(甄楚倩)惨被凌迟。事缘马与莲兄及未婚夫不打不相识，马、莲更互相倾慕。原来马为两江提督，表面正人君子，却趁机向莲嫂加以淫辱，莲目睹一切……   ",
            cast: "甄楚倩 , 郑浩南 , 林伟健 , 杨梵 , 杨雄 , 郑保瑞 , 初本科",
             videoUrl: "https://vip.ffzy-play6.com/20250831/41159_2708ccf2/index.m3u8"
        },
        {
            id: 3,
            title: "三奸",
            type: "movie",
            year: 2000,
            rating: 9.2,
            views: 1100000,
            poster: "https://img.feifeiimg.vip/upload/vod/20250904-1/03f21921de4b832e9e3311cacc856470.jpg",
            backdrop: "https://picsum.photos/800/400?random=3",
            plot: "两个“香港痴汉”为要争夺痴汉界之一哥地位而各出奇谋，各自四处捉女玩强奸，三个裸女惨被禁密室，困铁笼！",
            cast: "童宁 , 黄静 , 波子 , 蔡达华 , 钱耀荣",
            videoUrl: "https://vip.ffzy-play6.com/20250831/41162_9a5d1781/index.m3u8"
        },
        {
            id: 4,
            title: "天才，女友",
            type: "tv",
            year: 2026,
            rating: 9.4,
            views: 1500000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260802-1/76676d63cc4f463fa4f3d2e4817ca467.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260802-1/76676d63cc4f463fa4f3d2e4817ca467.jpg",
            plot: "根据素光同同名小说改编。江逾白长大以后，林知夏忽然对他说：“江逾白，我喜欢你，哲学和生物学意义上的喜欢。”那个夜晚，他脸颊微热，还听见自己加速的心跳声……。",
            cast: "田曦薇,胡一天,赖伟明,安沺,夏浩然,厉嘉琪,孙梦秋,李佑川,邬家楷",
            episodes: 28,
            episodeUrls: {
                1: "https://super.ffzy-online6.com/20260802/37946_f060fb7e/index.m3u8",
2: "https://super.ffzy-online6.com/20260802/37947_2ef80c94/index.m3u8",
3: "https://super.ffzy-online6.com/20260802/37948_5441a1aa/index.m3u8",
4: "https://super.ffzy-online6.com/20260802/37949_65f8842b/index.m3u8",
5: "https://super.ffzy-online6.com/20260802/37950_43050e88/index.m3u8",
6: "https://super.ffzy-online6.com/20260802/37951_d2ec9d53/index.m3u8",
7: "https://super.ffzy-online6.com/20260803/37990_1b7fc124/index.m3u8",
8: "https://super.ffzy-online6.com/20260803/37991_4c549e83/index.m3u8",
9: "https://super.ffzy-online6.com/20260804/38066_8d1bbb12/index.m3u8",
10: "https://super.ffzy-online6.com/20260804/38067_da03ec3d/index.m3u8",
11: "https://super.ffzy-online6.com/20260805/38138_b4fc37a8/index.m3u8",
12: "https://super.ffzy-online6.com/20260805/38139_ee4964b6/index.m3u8",
13: "https://super.ffzy-online6.com/20260806/38245_f2bc8e36/index.m3u8",
14: "https://super.ffzy-online6.com/20260806/38246_bf84bd4c/index.m3u8",
15: "https://super.ffzy-online6.com/20260807/38312_0ff9d418/index.m3u8",
16: "https://super.ffzy-online6.com/20260807/38313_d1c3af75/index.m3u8",
17: "https://super.ffzy-online6.com/20260808/38355_48e6328a/index.m3u8",
18: "https://super.ffzy-online6.com/20260808/38356_ffb55afa/index.m3u8",
19: "https://super.ffzy-online6.com/20260809/38395_e2bf12f3/index.m3u8",
20: "https://super.ffzy-online6.com/20260809/38396_b42f8dd1/index.m3u8",
21: "https://super.ffzy-online6.com/20260810/38473_2cf591de/index.m3u8",
22: "https://super.ffzy-online6.com/20260811/38555_923ccbce/index.m3u8",
23: "https://super.ffzy-online6.com/20260812/38631_17810cdd/index.m3u8",
24: "https://super.ffzy-online6.com/20260813/38703_e3a76242/index.m3u8",
25: "https://super.ffzy-online6.com/20260813/38704_d0fc7653/index.m3u8",
26: "https://super.ffzy-online6.com/20260813/38705_1585a6f1/index.m3u8",
27: "https://super.ffzy-online6.com/20260813/38706_fde68ded/index.m3u8",
28: "https://super.ffzy-online6.com/20260813/38707_0e989f4a/index.m3u8"
            }
        },
        {
            id: 5,
            title: "抓特务",
            type: "movie",
            year: 2026,
            rating: 7.4,
            views: 2000000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260815-1/203b7d5ccf2a9ae91df6cdabf0662f33.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260815-1/203b7d5ccf2a9ae91df6cdabf0662f33.jpg",
            plot: "一场警察与特务的较量，却展开满是烟火与温情的百姓人生。新中国成立之初，派出所所长肖大力（雷佳音 饰）凭直觉锁定潜伏在土唐刀胡同15号院的特务“5182”冯静波（胡歌 饰）。为追查真相，肖大力索性搬进院落，昔日追查者与目标成了朝夕相处的邻居。两人的交集，牵动着两个家庭、两代人的命运，岁月起落间满是波折与变故。当阅尽半生风雨，这对相伴相斗一辈子的冤家，早已不再是单纯的宿敌。影片根据张策的小说《无悔追踪》改编。",
            cast: "雷佳音,胡歌,啜妮,张瑶,林晓凡,杨舒伊,刘佩琦,姜武,杨青,陈国星,于震,黄曼,张雪迎,梁洁,于和伟,甘昀宸,王锐,蔡家明,丁文博,王啸宇",
            videoUrl: "https://vip.ffzy-play10.com/20260815/70132_b51f3eef/index.m3u8"
        },
        {
            id: 6,
            title: "能有多大事",
            type: "tv",
            year: 2016,
            rating: 8.8,
            views: 600000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260814-1/092d2185678e4e1ea55ab24a446910fe.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260814-1/092d2185678e4e1ea55ab24a446910fe.jpg",
            plot: "五星大厨许多金辛苦大半辈子，退休时衣食无忧，准备告诉儿女们自己和黄昏恋女友马小芬的婚事，还打算完婚后周游世界，哪知他幸福的晚年生活却因为小儿子被骗巨款完全乱了套。尽管法律条文认定成年孩子的债务与他没有任何连带关系，但是，为了减轻小儿子的罪行，为了一份担当，许多金卖房打工，走上“子债父偿”的艰辛路。而被骗的小儿子许小成在寻找骗子的讨债路上同样艰辛，最终他协助警察破获大案，还自己清白。尽管许多金子债父偿让家人误解，窘迫生活把他逼入死角，但他依然保持乐观的态度和做人的良心，树立了自己是“打不死的老强”的顽强形象。许多金的担当、坚韧、自强得到所有人的理解，他也闯出新天地，办起了了托老所，赢得马小芬忠贞不渝的爱情。",
            cast: "李幼斌,史兰芽,袁志博,孙桂田,马杰,迟志强,刘军",
            episodes: 36,
            episodeUrls: {
                1: "https://super.ffzy-online6.com/20260814/38789_238f9661/index.m3u8",
2: "https://super.ffzy-online6.com/20260814/38790_b9b993f4/index.m3u8",
3: "https://super.ffzy-online6.com/20260816/38909_b32cf553/index.m3u8",
4: "https://super.ffzy-online6.com/20260816/38910_2b3b9fe4/index.m3u8"
            }
        },
        {
            id: 7,
            title: "湘西剿匪记",
            type: "movie",
            year: 1987,
            rating: 7.7,
            views: 1800000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260612-1/2604f3aacc68f025528ac8d2a376b9f8.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260612-1/2604f3aacc68f025528ac8d2a376b9f8.jpg",
            plot: "解放前夕，国民党当局为阻挠中国人民解放军解放大西南，派员率土匪头目瞿天华之女瞿湘玉亲临湘西地区，重金收买当地土匪集团，建立“反共复兴基地”。解放军某部营长吴波奉命率部进驻凤山县，开展“清枪挤匪运动”。不料，带头交枪的贫农何长山被枪杀，师部宣传队员遭匪帮袭击遇难，吴波外出途中也遭土匪伏击。",
            cast: "魏宗万,薛淑洁,凌云,王喜元,崔明普,杜志国,赵秀丽,茹萍,丁汝骏,陈国典,胡湘光,魏小海,周予援,杨得志,陆树明,周其,杨宝河,庞燕,何为,蒋彪,李檀,张思洋,唐丽华,梁栋,曹希玲,周恰河,刘学群,何烈美,杨春武,李穗,周汉林",
            videoUrl: "https://vip.ffzy-play10.com/20260611/68488_7d77095f/index.m3u8"
        },
        {
            id: 8,
            title: "重器",
            type: "tv",
            year: 2026,
            rating: 8.7,
            views: 900000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260810-1/e74484f1acf116e76a9c227ffca32188.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260810-1/e74484f1acf116e76a9c227ffca32188.jpg",
            plot: "该剧讲述了改革开放初期1979年到1997年，陈一众、余高远、张丽慧、夏英杰、陆则铭五个法学院学生毕业后进入了司法领域不同的部门，在如火如荼的司法实践中见证历史、参与历史、与时俱进、不断成长的故事。",
            cast: "黄景瑜,蒋奇明,于和伟,闫佩伦,张佳宁,姜珮瑶,丁勇岱,王建国,尤勇智,宁理,成泰燊,齐奎,任程伟,朱颜曼滋,陈小艺,张帆,张国强,李洪涛,迟嘉,吴越,迟蓬,杨新鸣,宗俊涛,周野芒,胡可,胡杏儿,黄澄澄,翟小兴",
            episodes: 36,
            episodeUrls: {
                1: "https://super.ffzy-online6.com/20260810/38476_55f8c1c1/index.m3u8",
2: "https://super.ffzy-online6.com/20260810/38477_2149347b/index.m3u8",
3: "https://super.ffzy-online6.com/20260810/38478_c1351b04/index.m3u8",
4: "https://super.ffzy-online6.com/20260810/38479_fd009729/index.m3u8",
5: "https://super.ffzy-online6.com/20260811/38560_77e34271/index.m3u8",
6: "https://super.ffzy-online6.com/20260811/38561_3a69dd6e/index.m3u8",
7: "https://super.ffzy-online6.com/20260812/38633_c165343f/index.m3u8",
8: "https://super.ffzy-online6.com/20260812/38634_f00e84df/index.m3u8",
9: "https://super.ffzy-online6.com/20260813/38708_6764e7a4/index.m3u8",
10: "https://super.ffzy-online6.com/20260813/38709_8a46c74a/index.m3u8",
11: "https://super.ffzy-online6.com/20260814/38782_918d2d39/index.m3u8",
12: "https://super.ffzy-online6.com/20260814/38783_2ab50703/index.m3u8",
13: "https://super.ffzy-online6.com/20260815/38864_bed001dd/index.m3u8",
14: "https://super.ffzy-online6.com/20260815/38865_39c5e668/index.m3u8"
            }
        },
        {
            id: 9,
            title: "温柔的背叛",
            type: "tv",
            year: 2010,
            rating: 8.6,
            views: 750000,
            poster: "https://img.dytt-tupian.com/upload/vod/20250530-1/f0650b8f2e79b5d2c3d30549f9f5f6b0.jpg",
            backdrop: "hhttps://img.dytt-tupian.com/upload/vod/20250530-1/f0650b8f2e79b5d2c3d30549f9f5f6b0.jpg",
            plot: "世界上没有一个男人不渴望浪漫的恋情，当美好的恋情随着婚姻降临转化成亲情，人们便开始渴望另外一份浪漫而刺激的情感。于是，每个人潜意识里都觉得自己需要一个情人，婚外偷情不知从何时起已经成了一个已婚男人必须思考的问题。但是，人又怎能挣脱亲情、社会、道德、名誉和伦理的束缚。一份禁忌的感情到底是爱情的增值还是背叛，是情感的失衡还是归属，是苦涩的毒酒还是醇美的甘露？曾经幸福、宁静的家庭在突如其来的背叛面前，如同大海巨浪中的小船，承受致命的颠簸。家庭将何去何从。曾经美好、炙热的爱情在背叛与伤害之中分崩离析，情感和理智如同绷得笔直的细线，悬于一线之间。情感正遭受着前所未有的考验。残破不堪的爱情如何弥补，伤痕累累的心灵能否愈合。情感已沦陷，道德已沦丧，一段不堪回首的人生如何拯救。一切悬而未决，扑朔迷离，做出抉择的人们将付出怎样的代价……一段突如其来的感情到来。如何在面临道德与情感的抉择中做出正确的选择，是对每个人情感和理性的最大考验。刺激而诱人的不伦感情，在软玉温香背后隐藏着人们无法预知更无法操控的黑色旋涡，它吞噬着人们的理智、名誉、金钱、权利，吞噬掉一切你拥有的，想要的，在乎的！而它又散发着迷人的甜香，充满着诱惑，让人挥之不去、欲罢不能。它，就时刻包围着每个人，就在我们的身边，就在你我的身后……",
            cast: "贾青,肖涵,牛萌萌,王笛",
           episodes: 34,
            episodeUrls: {
              1: "https://vip.dytt-play.com/20250529/24027_544c3351/index.m3u8",
2: "https://vip.dytt-play.com/20250529/24028_d3edf794/index.m3u8",
3: "https://vip.dytt-play.com/20250529/24029_383d8600/index.m3u8",
4: "https://vip.dytt-play.com/20250529/24030_5de67554/index.m3u8",
5: "https://vip.dytt-play.com/20250529/24031_8474b860/index.m3u8",
6: "https://vip.dytt-play.com/20250529/24032_1fb42507/index.m3u8",
7: "https://vip.dytt-play.com/20250529/24033_bc06828a/index.m3u8",
8: "https://vip.dytt-play.com/20250529/24034_c618f687/index.m3u8",
9: "https://vip.dytt-play.com/20250529/24035_5ea2f0a1/index.m3u8",
10: "https://vip.dytt-play.com/20250529/24036_251713b2/index.m3u8",
11: "https://vip.dytt-play.com/20250529/24037_9f15dd77/index.m3u8",
12: "https://vip.dytt-play.com/20250529/24038_baf24e5f/index.m3u8",
13: "https://vip.dytt-play.com/20250529/24039_f331c4fb/index.m3u8",
14: "https://vip.dytt-play.com/20250529/24040_f4d4c95a/index.m3u8",
15: "https://vip.dytt-play.com/20250529/24041_109673a9/index.m3u8",
16: "https://vip.dytt-play.com/20250529/24042_fdb6188f/index.m3u8",
17: "https://vip.dytt-play.com/20250529/24043_b55b1e5d/index.m3u8",
18: "https://vip.dytt-play.com/20250529/24044_5937bc13/index.m3u8",
19: "https://vip.dytt-play.com/20250529/24045_ffad99a1/index.m3u8",
20: "https://vip.dytt-play.com/20250529/24046_3cdc0a29/index.m3u8",
21: "https://vip.dytt-play.com/20250529/24047_bad65e96/index.m3u8",
22: "https://vip.dytt-play.com/20250529/24048_d5447db4/index.m3u8",
23: "https://vip.dytt-play.com/20250529/24049_e16851c2/index.m3u8",
24: "https://vip.dytt-play.com/20250529/24050_9a8bf55d/index.m3u8",
25: "https://vip.dytt-play.com/20250529/24051_f0838b2e/index.m3u8",
26: "https://vip.dytt-play.com/20250529/24052_72e754f6/index.m3u8",
27: "https://vip.dytt-play.com/20250529/24053_e3fce7f4/index.m3u8",
28: "https://vip.dytt-play.com/20250529/24054_042237ea/index.m3u8",
29: "https://vip.dytt-play.com/20250529/24055_14842d88/index.m3u8",
30: "https://vip.dytt-play.com/20250529/24056_82c3cf12/index.m3u8",
31: "https://vip.dytt-play.com/20250529/24057_678548c3/index.m3u8",
32: "https://vip.dytt-play.com/20250529/24058_67249303/index.m3u8",
33: "https://vip.dytt-play.com/20250529/24059_ae4a7f24/index.m3u8",
34: "https://vip.dytt-play.com/20250529/24060_6f2fed8e/index.m3u8"
            }
        },
        {
            id: 10,
            title: "温柔的诱惑",
            type: "tv",
            year: 2014,
            rating: 8.6,
            views: 820000,
            poster: "https://img.dytt-tupian.com/upload/vod/20250930-1/4cdf2a8a4c5a050263d8e21eae3c8c20.jpg",
            backdrop: "https://img.dytt-tupian.com/upload/vod/20250930-1/4cdf2a8a4c5a050263d8e21eae3c8c20.jpg",
            plot: "剧中韩烨饰演的廖心雨因与即将出版 著作的黄中石（侯天来饰）暧昧，被黄中 石妻子发现，而大大出手伤及廖心雨，而 在住院的同时又闻讯自己实习的出版社领 导因贪污被双规，此时的廖心雨已无容身 之所，在黄中石的好心帮助下而暂住在他 的家里，却不料是引狼入室，这位从小城 市出来的大学生，为了能留住在大城市 中，一次又一次去勾引黄中石，闹的黄家 鸡飞狗跳，但是每次被妻子文丽（杨梓嫣 饰）发现后，这位外表清纯内心腹黑的小 三都能化险为夷。",
            cast: "侯天来,韩烨,杨紫嫣,张戈,安泽豪,顾佳,林京来",
            episodes: 38,
            episodeUrls: {
               1: "https://vip.dytt-tvb.com/20250926/41312_e76d0860/index.m3u8",
2: "https://vip.dytt-tvb.com/20250926/41313_ba662e16/index.m3u8",
3: "https://vip.dytt-tvb.com/20250926/41314_16d134c1/index.m3u8",
4: "https://vip.dytt-tvb.com/20250926/41315_ed164c24/index.m3u8",
5: "https://vip.dytt-tvb.com/20250926/41316_efc508ca/index.m3u8",
6: "https://vip.dytt-tvb.com/20250926/41317_762ce087/index.m3u8",
7: "https://vip.dytt-tvb.com/20250926/41318_9fd10588/index.m3u8",
8: "https://vip.dytt-tvb.com/20250926/41319_739914b0/index.m3u8",
9: "https://vip.dytt-tvb.com/20250926/41320_adf25053/index.m3u8",
10: "https://vip.dytt-tvb.com/20250926/41321_630a1e2b/index.m3u8",
11: "https://vip.dytt-tvb.com/20250926/41322_73905701/index.m3u8",
12: "https://vip.dytt-tvb.com/20250926/41323_0870efc2/index.m3u8",
13: "https://vip.dytt-tvb.com/20250926/41324_a397cbaf/index.m3u8",
14: "https://vip.dytt-tvb.com/20250926/41325_31e1e2ef/index.m3u8",
15: "https://vip.dytt-tvb.com/20250926/41326_5f646a58/index.m3u8",
16: "https://vip.dytt-tvb.com/20250926/41327_b05d4080/index.m3u8",
17: "https://vip.dytt-tvb.com/20250926/41328_c9326f7c/index.m3u8",
18: "https://vip.dytt-tvb.com/20250926/41329_bd9c29a7/index.m3u8",
19: "https://vip.dytt-tvb.com/20250926/41330_3cccf2bb/index.m3u8",
20: "https://vip.dytt-tvb.com/20250926/41331_33eb7b58/index.m3u8",
21: "https://vip.dytt-tvb.com/20250926/41332_8f118ab2/index.m3u8",
22: "https://vip.dytt-tvb.com/20250926/41333_d845e2d9/index.m3u8",
23: "https://vip.dytt-tvb.com/20250926/41334_b9a460fc/index.m3u8",
24: "https://vip.dytt-tvb.com/20250926/41335_c850544e/index.m3u8",
25: "https://vip.dytt-tvb.com/20250926/41336_834119d8/index.m3u8",
26: "https://vip.dytt-tvb.com/20250926/41337_ff1aa43b/index.m3u8",
27: "https://vip.dytt-tvb.com/20250926/41338_2b6319cb/index.m3u8",
28: "https://vip.dytt-tvb.com/20250926/41339_0bd0283d/index.m3u8",
29: "https://vip.dytt-tvb.com/20250926/41340_cc046c8e/index.m3u8",
30: "https://vip.dytt-tvb.com/20250926/41341_78d78a6d/index.m3u8",
31: "https://vip.dytt-tvb.com/20250926/41342_9cd8c71d/index.m3u8",
32: "https://vip.dytt-tvb.com/20250926/41343_3866ba7b/index.m3u8",
33: "https://vip.dytt-tvb.com/20250926/41344_b241600b/index.m3u8",
34: "https://vip.dytt-tvb.com/20250926/41345_950b5a06/index.m3u8",
35: "https://vip.dytt-tvb.com/20250926/41346_d2339e22/index.m3u8",
36: "https://vip.dytt-tvb.com/20250926/41347_10489e28/index.m3u8",
37: "https://vip.dytt-tvb.com/20250926/41348_9cc38fba/index.m3u8",
38: "https://vip.dytt-tvb.com/20250926/41349_8a759188/index.m3u8"
            }
        },
         {
            id: 11,
            title: "生万物",
            type: "tv",
            year: 2025,
            rating: 8.3,
            views: 1300000,
            poster: "https://tupian.ffeiimg.com/upload/vod/20250813-1/d6581dbe3b7a1ae92138e24ccd429ea1.jpg",
            backdrop: "https://picsum.photos/800/400?random=11",
            plot: "《生万物》根据荣获第三届人民文学奖的长篇小说《缱绻与决绝》改编，以鲁南农村土地变迁为背景，讲述了以宁绣绣、封大脚、费左氏为代表的宁、封、费三个家族、两代人的兴衰史。天牛庙村村民为同一片土地不断努力，是跨越多年打不散的邻里情，也是老一辈农民对土地的敬畏与依恋。",
            cast: "杨幂,欧豪,倪大红,秦海璐,邢菲,张天阳,张海宇,迟蓬,孙绍龙,林永健,蓝盈莹,赵达,宋家腾,潘之琳,艾东,于震",
            episodes: 36,
            episodeUrls: {
                1: "https://vip.ffzy-plays.com/20250813/44166_e26cee6a/index.m3u8",
                2: "https://vip.ffzy-plays.com/20250813/44167_2f893e83/index.m3u8",
3: "https://vip.ffzy-plays.com/20250813/44168_f9358d6a/index.m3u8",
4: "https://vip.ffzy-plays.com/20250813/44169_e8520871/index.m3u8",
5: "https://vip.ffzy-plays.com/20250813/44170_cbdc5c18/index.m3u8",
6: "https://vip.ffzy-plays.com/20250814/44215_3138f2d1/index.m3u8",
7: "https://vip.ffzy-plays.com/20250814/44216_b42ec7cf/index.m3u8",
8: "https://vip.ffzy-plays.com/20250814/44217_240d037d/index.m3u8",
9: "https://vip.ffzy-plays.com/20250815/44252_157d3e34/index.m3u8",
10: "https://vip.ffzy-plays.com/20250815/44251_fc8fb730/index.m3u8",
11: "https://vip.ffzy-plays.com/20250815/44250_ba077326/index.m3u8",
12: "https://vip.ffzy-plays.com/20250816/44288_eabe8e1f/index.m3u8",
13: "https://vip.ffzy-plays.com/20250816/44286_9f2dab58/index.m3u8",
14: "https://vip.ffzy-plays.com/20250816/44287_ef17e89d/index.m3u8",
15: "https://vip.ffzy-plays.com/20250817/44336_0941839a/index.m3u8",
16: "https://vip.ffzy-plays.com/20250817/44337_ac6193b0/index.m3u8",
17: "https://vip.ffzy-plays.com/20250817/44338_e261790d/index.m3u8",
18: "https://vip.ffzy-plays.com/20250818/44365_bd9a32d4/index.m3u8",
19: "https://vip.ffzy-plays.com/20250818/44366_b66b4b5b/index.m3u8",
20: "https://vip.ffzy-plays.com/20250818/44367_dd31b8ef/index.m3u8",
21: "https://vip.ffzy-plays.com/20250819/44395_b223756d/index.m3u8",
22: "https://vip.ffzy-plays.com/20250819/44396_4e6e15ac/index.m3u8",
23: "https://vip.ffzy-plays.com/20250819/44397_31e2835b/index.m3u8",
24: "https://vip.ffzy-plays.com/20250820/44423_b1a3fd1d/index.m3u8",
25: "https://vip.ffzy-plays.com/20250820/44424_ff36b418/index.m3u8",
26: "https://vip.ffzy-plays.com/20250820/44425_c1bdb162/index.m3u8",
27: "https://vip.ffzy-plays.com/20250821/44463_c724bedf/index.m3u8",
28: "https://vip.ffzy-plays.com/20250821/44464_853b3b5c/index.m3u8",
29: "https://vip.ffzy-plays.com/20250821/44465_506f8a18/index.m3u8",
30: "https://vip.ffzy-plays.com/20250822/44503_a6104a16/index.m3u8",
31: "https://vip.ffzy-plays.com/20250822/44504_43f2d164/index.m3u8",
32: "https://vip.ffzy-plays.com/20250822/44505_313fa9a7/index.m3u8",
33: "https://vip.ffzy-plays.com/20250823/44539_681e15dd/index.m3u8",
34: "https://vip.ffzy-plays.com/20250823/44541_2acada6d/index.m3u8",
35: "https://vip.ffzy-plays.com/20250823/44540_5160bab0/index.m3u8",
36: "https://vip.ffzy-plays.com/20250823/44542_54a246ca/index.m3u8"
            }
        },

{
            id: 12,
            title: "消失的人",
            type: "movie",
            year: 2026,
            rating: 8.3,
            views: 1300000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260505-1/d841792e46816e455831de55e25a7d55.jpg",
            backdrop: "https://picsum.photos/800/400?random=11",
            plot: " 一个寻常的清晨，唐宇（郑恺 饰）的儿子在楼梯间凭空消失；隔壁单元里，独居女孩林雨彤（刘浩存 饰）在熟睡中被人侵犯；赌鬼租客严午（邱泽 饰）满身血污，谎编踪迹藏匿尸体。这栋看似平静、不起眼的居民楼里，每个擦肩而过的“熟人”，都有可能是藏着獠牙的恶鬼。当人心比鬼更可怕时，你每天出入的家门，都可能是通往地狱的入口……本片改编自豆瓣阅读连载小说《海葵》，作者贝客邦。",
            cast: "郑恺,刘浩存,邱泽,李晨,姜妍,黄小蕾,李梦,张琪,毕雯珺,冯兵,滕哲,冯雪雅,汤心一",
            videoUrl:"https://super.ffzy-online6.com/20260731/37740_e6a88dfc/index.m3u8"
        },

{
            id: 14,
            title: "深情为饵",
            type: "tv",
            year: 2026,
            rating: 0.0,
            views: 600000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260810-1/9e2482287870fae1800f079d82ef4cae.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260810-1/9e2482287870fae1800f079d82ef4cae.jpg",
            plot: "陆早早突遭意外，竟穿越成民国少夫人苏沐晚，醒来，却是丈夫枪口相对、父母冤案、连环下毒……她于绝境中步步破局，与“醋精”少爷凌慎行从生死对立到情根深重，可就在浓情蜜意时，她骤然梦醒归现代，转角竟撞见那个熟悉的“他”！",
            cast: "邢昭林,方瑾,常喆宽,尹蕊,王沛然,赵悠图",
            episodes: 36,
            episodeUrls: {
            1: "https://super.ffzy-online6.com/20260810/38448_0d7f2e50/index.m3u8",
2: "https://super.ffzy-online6.com/20260810/38449_b4624271/index.m3u8",
3: "https://super.ffzy-online6.com/20260810/38450_4fedf8be/index.m3u8",
4: "https://super.ffzy-online6.com/20260810/38451_261258fa/index.m3u8",
5: "https://super.ffzy-online6.com/20260810/38452_d31687df/index.m3u8",
6: "https://super.ffzy-online6.com/20260810/38453_94d5f5ee/index.m3u8",
7: "https://super.ffzy-online6.com/20260810/38454_e6964314/index.m3u8",
8: "https://super.ffzy-online6.com/20260810/38455_08159ead/index.m3u8",
9: "https://super.ffzy-online6.com/20260811/38509_8bd73c11/index.m3u8",
10: "https://super.ffzy-online6.com/20260811/38510_67f55bdf/index.m3u8",
11: "https://super.ffzy-online6.com/20260812/38583_0470d319/index.m3u8",
12: "https://super.ffzy-online6.com/20260812/38584_3d12d287/index.m3u8",
13: "https://super.ffzy-online6.com/20260813/38657_d74dc3f2/index.m3u8",
14: "https://super.ffzy-online6.com/20260813/38658_52258718/index.m3u8",
15: "https://super.ffzy-online6.com/20260814/38737_423822e8/index.m3u8",
16: "https://super.ffzy-online6.com/20260814/38738_dd3c26a2/index.m3u8",
17: "https://super.ffzy-online6.com/20260815/38807_f8e10f59/index.m3u8",
18: "https://super.ffzy-online6.com/20260815/38808_23035635/index.m3u8",
19: "https://super.ffzy-online6.com/20260816/38893_c1d6eb5a/index.m3u8",
20: "https://super.ffzy-online6.com/20260816/38894_dea4e200/index.m3u8"
            }
        },


{
            id: 15,
            title: "御廷谣",
            type: "tv",
            year: 2026,
            rating: 8.8,
            views: 600000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260729-1/20893c4673f6a29047161dac1354b97f.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260729-1/20893c4673f6a29047161dac1354b97f.jpg",
            plot: "改编自行烟烟的同名小说。孟廷辉，大平王朝有史以来个以女子进士科三元及第入翰林院的奇女子。十年前的她被他从死人堆里救出来，蓬头垢面口齿不清。十年后的她才学满腹、冠盖众人，于女子进士科上大方异彩，成为了朝中二十余年来令人瞩目的女官。她处事圆滑，心机多端，为了往上爬而不择手段，视飞黄腾达为人生目标，数年来从一个小小的正六品翰林院修撰一路升为与诸多老臣平起平坐的参知政事，被朝中清议冠以奸佞之名而不在乎，行事苛酷阴狠、对人不留余地，令一朝上下人人畏恶，可却没有一个人懂得她这么多年是如何过来的，更没有一个人知晓她心里究竟想的是什么。那一个龙座高高在上，那一个男人聛睨天下，她心甘情愿地伏在他座下，看他固江山，看他养百姓，看他治太平，看他一步步成为大平王朝有史以来最强悍的一个帝王。当年他救了她，现如今她报答他，就算是成为他一世帝业的垫脚石也无怨无悔，可她算尽了一切，却唯独没有算到，其实他也爱着她。一个是尽享天下盛名的英明雄主，一个是背负千古骂名的奸佞之臣，他们的情路注定布满荆棘。",
            cast: "李吴谨言,陈哲远,梁永棋,赵昭仪,张南,郭品超,盛一伦,吴岱融,黄祖鑫,宋麒",
            episodes: 36,
            episodeUrls: {
                
            }
        },

          { id: 16,
            title: "浣纱录",
            type: "tv",
            year: 2026,
            rating: 8.8,
            views: 600000,
            poster: "https://img.feifeiimg.vip/upload/vod/20260701-1/b547ac4dc1a68681b86c74419a5f880b.jpg",
            backdrop: "https://img.feifeiimg.vip/upload/vod/20260701-1/b547ac4dc1a68681b86c74419a5f880b.jpg",
            plot: "出品单位：广东共赢影视传媒有限公司、星辉（佛山）影视产业有限公司制作公司：广东辉映影视传媒有限公司出品人：霍志健、周伟全联合出品人：朱牧言、姚忠、Mr豆总制片人：邵明江艺术指导：张腾之音乐指导：张宏光、程矛非遗顾问：欧阳凤婷制片人：李功斌、黄彩丽、黄莉联合制片人：刘昕、周晓茜、潘光俊、姚嘉元导演：保密编剧：云霄播放平台：腾讯视频选角团队：一丁选角选角导演：小宇演员统筹：王孜繁演员副导演：小天项目类型：古装、轻喜 …",
            cast: "蒙恩,胡丹丹",
            episodes: 36,
            episodeUrls: {
            1: "https://svip.feifei-play.com/20260701/46285_47ebca26/index.m3u8",
2: "https://svip.feifei-play.com/20260701/46286_dcdbcb38/index.m3u8",
3: "https://svip.feifei-play.com/20260701/46287_e73dbb39/index.m3u8",
4: "https://svip.feifei-play.com/20260701/46288_2800a901/index.m3u8",
5: "https://svip.feifei-play.com/20260701/46289_f0b314f1/index.m3u8",
6: "https://svip.feifei-play.com/20260701/46290_5b932035/index.m3u8",
7: "https://svip.feifei-play.com/20260701/46291_2ad85be3/index.m3u8",
8: "https://svip.feifei-play.com/20260701/46292_bcfaf6aa/index.m3u8",
9: "https://svip.feifei-play.com/20260702/46349_5499df60/index.m3u8",
10: "https://svip.feifei-play.com/20260702/46350_ed354d90/index.m3u8",
11: "https://super.ffzy-online6.com/20260703/35894_b9fdece3/index.m3u8",
12: "https://super.ffzy-online6.com/20260703/35895_d7953775/index.m3u8",
13: "https://super.ffzy-online6.com/20260704/35972_33ca818e/index.m3u8",
14: "https://super.ffzy-online6.com/20260704/35973_eb653e56/index.m3u8",
15: "https://super.ffzy-online6.com/20260705/36039_08178acd/index.m3u8",
16: "https://super.ffzy-online6.com/20260705/36040_759d94ed/index.m3u8",
17: "https://super.ffzy-online6.com/20260706/36087_55a2471f/index.m3u8",
18: "https://super.ffzy-online6.com/20260707/36149_4a378e98/index.m3u8",
19: "https://super.ffzy-online6.com/20260708/36202_6b7e5dd8/index.m3u8",
20: "https://super.ffzy-online6.com/20260709/36244_5566af11/index.m3u8",
21: "https://super.ffzy-online6.com/20260709/36245_1774650c/index.m3u8",
22: "https://super.ffzy-online6.com/20260709/36246_54b26778/index.m3u8",
23: "https://super.ffzy-online6.com/20260709/36247_e8b10d1e/index.m3u8",
24: "https://super.ffzy-online6.com/20260709/36248_5800ccd9/index.m3u8"
            }
        },



 {
            id: 17,
            title: "济宁新闻综合",
            type: "movie",
            year: 1987,
            rating: 7.7,
            views: 1800000,
            poster: "https://raw.giteeusercontent.com/qddl/qianggeyingshi/raw/master/picture/%E6%B5%8E%E5%AE%81%E7%BB%BC%E5%90%88.jpeg?metadata=eyJyIjoibWFzdGVyIiwiZnAiOiJwaWN0dXJlL-a1juWugee7vOWQiC5qcGVnIiwidWlkIjoxNDQ3MTQyMywicGlkIjozNzUzODU1NCwic3RvIjoiZ2l0LXNoYXJkaW5nLXN0by00MnQtMDAyIiwicnAiOiJyZXBvcy9jYy9hZi9jY2FmNmQ5OGQxOTBmZGMxMTRhNDhmODBkYTY2YjUxOGZjYzQ0MzRhOGQyMDIyOWFjMzU1NWU3ZjBmN2JmZjVmLmdpdCIsImlzcCI6dHJ1ZSwiZXhwaXJlX2F0IjoxNzg2OTQ2NDAwfQ&signature=EyJrdUqvKydOZltMGWcCtsIphb28n5hKgR405o9HgYk",
            backdrop: "https://raw.giteeusercontent.com/qddl/qianggeyingshi/raw/master/picture/%E6%B5%8E%E5%AE%81%E7%BB%BC%E5%90%88.jpeg",
            plot: "",
            cast: "",
            videoUrl: "https://srs.jnnews.tv/video/JTV-1/index.m3u8"
        },


        {
            id: 13,
            title: "回家的诱惑",
            type: "tv",
            year: 2011,
            rating: 8.5,
            views: 1600000,
            poster: "https://img.feifeiimg.vip/upload/vod/20221121-1/c08b2718bff92c2d84d561101a1cd4e5.jpg",
            backdrop: "https://picsum.photos/800/400?random=12",
            plot: "在周围人的眼中，林品如（秋瓷炫 饰）与洪世贤（凌潇肃 饰）是一对让人欣羡的模范情侣，二人郎才女貌，天作碧合，但是家家都有一本难念的经。随着时光的流逝，婚姻之初的浪漫情怀渐渐转淡，取而代之的是生活中的各种现实问题。品如因始终未能怀孕而遭到婆婆指摘，日常生活里充满了磕磕绊绊。与此同时，品如留学法国的闺蜜艾莉（李彩桦 饰）突然带着小男孩尚恩（朱佳煜 饰）",
            cast: "秋瓷炫,李彩桦,凌潇肃,郑逸桐,夏台凤,迟帅,涂黎曼,馨子,雷佳音,巴戈,张芝华,黄达亮,刘洁,朱佳煜",
            episodes: 68,
            episodeUrls: {
                1: "https://vip.ffzy-play.com/20221122/22107_61cb3f80/index.m3u8",
2: "https://vip.ffzy-play.com/20221122/22108_e54fad2d/index.m3u8",
3: "https://vip.ffzy-play.com/20221122/22109_47be46f2/index.m3u8",
4: "https://vip.ffzy-play.com/20221122/22110_4d0685b9/index.m3u8",
5: "https://vip.ffzy-play.com/20221122/22111_e7113670/index.m3u8",
6: "https://vip.ffzy-play.com/20221122/22112_51cd3201/index.m3u8",
7: "https://vip.ffzy-play.com/20221122/22114_2eb252c2/index.m3u8",
8: "https://vip.ffzy-play.com/20221122/22113_1abfe7be/index.m3u8",
9: "https://vip.ffzy-play.com/20221122/22116_56514df1/index.m3u8",
10: "https://vip.ffzy-play.com/20221122/22117_82ccdae2/index.m3u8",
11: "https://vip.ffzy-play.com/20221122/22115_bfb5831d/index.m3u8",
12: "https://vip.ffzy-play.com/20221122/22118_fcf3031d/index.m3u8",
13: "https://vip.ffzy-play.com/20221122/22119_70eb4a63/index.m3u8",
14: "https://vip.ffzy-play.com/20221122/22120_05593c9e/index.m3u8",
15: "https://vip.ffzy-play.com/20221122/22121_d81c1f21/index.m3u8",
16: "https://vip.ffzy-play.com/20221122/22122_41ead1c9/index.m3u8",
17: "https://vip.ffzy-play.com/20221122/22123_48a47480/index.m3u8",
18: "https://vip.ffzy-play.com/20221122/22124_3896f110/index.m3u8",
19: "https://vip.ffzy-play.com/20221122/22125_72de6031/index.m3u8",
20: "https://vip.ffzy-play.com/20221122/22126_cacd38ad/index.m3u8",
21: "https://vip.ffzy-play.com/20221122/22127_da5d29d2/index.m3u8",
22: "https://vip.ffzy-play.com/20221122/22128_f198a842/index.m3u8",
23: "https://vip.ffzy-play.com/20221122/22129_a916e137/index.m3u8",
24: "https://vip.ffzy-play.com/20221122/22130_8c73edbb/index.m3u8",
25: "https://vip.ffzy-play.com/20221122/22131_9ac49b3c/index.m3u8",
26: "https://vip.ffzy-play.com/20221122/22132_f7a130d1/index.m3u8",
27: "https://vip.ffzy-play.com/20221122/22133_ea094b4d/index.m3u8",
28: "https://vip.ffzy-play.com/20221122/22135_7bbc8989/index.m3u8",
29: "https://vip.ffzy-play.com/20221122/22134_c8001a18/index.m3u8",
30: "https://vip.ffzy-play.com/20221122/22137_1828c424/index.m3u8",
31: "https://vip.ffzy-play.com/20221122/22136_8d6300e8/index.m3u8",
32: "https://vip.ffzy-play.com/20221122/22138_46976fef/index.m3u8",
33: "https://vip.ffzy-play.com/20221122/22139_682fcd2c/index.m3u8",
34: "https://vod.feifei-online.com/20230711/29347_6aeb06ad/index.m3u8",
35: "https://vod.feifei-online.com/20230711/29348_f2db0a2d/index.m3u8",
36: "https://vod.feifei-online.com/20230710/29314_3c4f1351/index.m3u8",
37: "https://vod.feifei-online.com/20230710/29315_11f209d3/index.m3u8",
38: "https://vod.feifei-online.com/20230710/29316_08fcffde/index.m3u8",
39: "https://vod.feifei-online.com/20230710/29317_68bd221e/index.m3u8",
40: "https://vod.feifei-online.com/20230710/29318_d8937212/index.m3u8",
41: "https://vod.feifei-online.com/20230710/29319_d6100654/index.m3u8",
42: "https://vod.feifei-online.com/20230710/29320_428e8879/index.m3u8",
43: "https://vod.feifei-online.com/20230710/29321_c69d2687/index.m3u8",
44: "https://vod.feifei-online.com/20230710/29322_0f32b4fe/index.m3u8",
45: "https://vod.feifei-online.com/20230710/29323_d198b220/index.m3u8",
46: "https://vod.feifei-online.com/20230711/29324_84093d65/index.m3u8",
47: "https://vod.feifei-online.com/20230711/29325_d1ac0fb9/index.m3u8",
48: "https://vod.feifei-online.com/20230711/29326_181a4658/index.m3u8",
49: "https://vod.feifei-online.com/20230711/29328_38f5ad46/index.m3u8",
50: "https://vod.feifei-online.com/20230711/29327_9a2c2684/index.m3u8",
51: "https://vod.feifei-online.com/20230711/29329_a0f95d56/index.m3u8",
52: "https://vod.feifei-online.com/20230711/29330_9e8a54e3/index.m3u8",
53: "https://vod.feifei-online.com/20230711/29331_06550240/index.m3u8",
54: "https://vod.feifei-online.com/20230711/29332_82db81c8/index.m3u8",
55: "https://vod.feifei-online.com/20230711/29333_c09ab34e/index.m3u8",
56: "https://vod.feifei-online.com/20230711/29334_8cfb1922/index.m3u8",
57: "https://vod.feifei-online.com/20230711/29335_bda95476/index.m3u8",
58: "https://vod.feifei-online.com/20230711/29336_1e070fa9/index.m3u8",
59: "https://vod.feifei-online.com/20230711/29337_f0d0ea51/index.m3u8",
60: "https://vod.feifei-online.com/20230711/29338_e54c0ec2/index.m3u8",
61: "https://vod.feifei-online.com/20230711/29339_220e6783/index.m3u8",
62: "https://vod.feifei-online.com/20230711/29340_c5992708/index.m3u8",
63: "https://vod.feifei-online.com/20230711/29341_95660983/index.m3u8",
64: "https://vod.feifei-online.com/20230711/29342_a4b9a36e/index.m3u8",
65: "https://vod.feifei-online.com/20230711/29349_0470b7b4/index.m3u8",
66: "https://vod.feifei-online.com/20230711/29343_7ec69600/index.m3u8",
67: "https://vod.feifei-online.com/20230711/29350_679d3070/index.m3u8",
68: "https://vod.feifei-online.com/20230711/29351_ab8dde44/index.m3u8"
            }
        }
    ],

    // 初始化
    init: function() {
        this.state.data = [...this.mockData];
        this.renderHero();
        this.renderGrid();
        this.setupEventListeners();
    },

    // 设置事件监听
    setupEventListeners: function() {
        document.getElementById('searchInput').addEventListener('keyup', (e) => {
            if (e.key === 'Enter') this.handleSearch();
        });
    },

    // 渲染英雄区
    renderHero: function() {
        // 随机选择一个热门电影作为Hero
        const heroItem = this.mockData[0];
        this.state.currentHeroId = heroItem.id;
        document.getElementById('heroTitle').innerText = heroItem.title;
        document.getElementById('heroDesc').innerText = heroItem.plot;
    },

    // 获取过滤后的数据
    getFilteredData: function() {
        let filtered = this.state.data;

        // 类型过滤
        if (this.state.currentFilter !== 'all') {
            filtered = filtered.filter(item => item.type === this.state.currentFilter);
        }

        // 搜索过滤
        if (this.state.searchQuery) {
            const query = this.state.searchQuery.toLowerCase();
            filtered = filtered.filter(item => 
                item.title.toLowerCase().includes(query) || 
                item.cast.toLowerCase().includes(query)
            );
        }

        // 排序
        filtered.sort((a, b) => {
            if (this.state.sortBy === 'rating') return b.rating - a.rating;
            if (this.state.sortBy === 'views') return b.views - a.views;
            return b.year - a.year; // newest
        });

        return filtered;
    },

    // 渲染网格
    renderGrid: function() {
        const grid = document.getElementById('movieGrid');
        grid.innerHTML = '';
        
        const filtered = this.getFilteredData();
        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / this.state.itemsPerPage);
        
        // 边界检查
        if (this.state.currentPage > totalPages && totalPages > 0) this.state.currentPage = totalPages;
        if (this.state.currentPage < 1) this.state.currentPage = 1;

        const start = (this.state.currentPage - 1) * this.state.itemsPerPage;
        const end = start + this.state.itemsPerPage;
        const pageData = filtered.slice(start, end);

        if (pageData.length === 0) {
            grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-500">未找到相关影视内容</div>';
        } else {
            pageData.forEach(item => {
                const card = document.createElement('div');
                card.className = 'movie-card bg-gray-800 rounded-lg overflow-hidden cursor-pointer group relative';
                card.onclick = () => this.showDetails(item.id);
                
                card.innerHTML = `
                    <div class="relative aspect-[2/3] overflow-hidden">
                        <img src="${item.poster}" alt="${item.title} Poster" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute top-2 right-2 bg-black bg-opacity-60 text-yellow-400 text-xs font-bold px-2 py-1 rounded flex items-center">
                            <i class="fas fa-star mr-1"></i> ${item.rating}
                        </div>
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all flex items-center justify-center">
                            <i class="fas fa-play-circle text-5xl text-white opacity-0 group-hover:opacity-100 transition-opacity transform scale-50 group-hover:scale-100"></i>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full p-2 bg-gradient-to-t from-black to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                             <span class="text-xs text-white bg-red-600 px-1 rounded">${item.type === 'movie' ? '电影' : '电视剧'}</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="font-bold text-lg truncate text-white group-hover:text-red-400 transition-colors">${item.title}</h3>
                        <div class="flex justify-between items-center text-xs text-gray-400 mt-1">
                            <span>${item.year}</span>
                            <span><i class="fas fa-eye mr-1"></i> ${(item.views / 10000).toFixed(1)}万</span>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        this.renderPagination(totalPages);
    },

    // 渲染分页
    renderPagination: function(totalPages) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';
        
        if (totalPages <= 1) return;

        // 上一页
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.className = `px-3 py-1 rounded bg-gray-800 border border-gray-700 hover:bg-gray-700 ${this.state.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}`;
        prevBtn.disabled = this.state.currentPage === 1;
        prevBtn.onclick = () => { if(this.state.currentPage > 1) { this.state.currentPage--; this.renderGrid(); window.scrollTo(0,0); }};
        pagination.appendChild(prevBtn);

        // 页码
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.innerText = i;
            btn.className = `px-3 py-1 rounded border ${this.state.currentPage === i ? 'bg-red-600 border-red-600 text-white' : 'bg-gray-800 border-gray-700 hover:bg-gray-700'}`;
            btn.onclick = () => { this.state.currentPage = i; this.renderGrid(); window.scrollTo(0,0); };
            pagination.appendChild(btn);
        }

        // 下一页
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.className = `px-3 py-1 rounded bg-gray-800 border border-gray-700 hover:bg-gray-700 ${this.state.currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}`;
        nextBtn.disabled = this.state.currentPage === totalPages;
        nextBtn.onclick = () => { if(this.state.currentPage < totalPages) { this.state.currentPage++; this.renderGrid(); window.scrollTo(0,0); }};
        pagination.appendChild(nextBtn);
    },

    // 处理搜索
    handleSearch: function() {
        const input = document.getElementById('searchInput');
        this.state.searchQuery = input.value.trim();
        this.state.currentPage = 1;
        this.renderGrid();
    },

    // 处理类型过滤
    filterType: function(type) {
        this.state.currentFilter = type;
        this.state.currentPage = 1;
        this.renderGrid();
    },

    // 处理排序
    handleSort: function() {
        const select = document.getElementById('sortSelect');
        this.state.sortBy = select.value;
        this.renderGrid();
    },

    // 返回首页
    goHome: function() {
        this.state.currentFilter = 'all';
        this.state.searchQuery = '';
        document.getElementById('searchInput').value = '';
        this.state.currentPage = 1;
        this.renderGrid();
        window.scrollTo(0,0);
    },

    // 显示详情模态框
    showDetails: function(id) {
        const item = this.mockData.find(m => m.id === id);
        if (!item) return;

        document.getElementById('modalTitle').innerText = item.title;
        document.getElementById('modalYear').innerText = item.year;
        document.getElementById('modalRating').innerHTML = `<i class="fas fa-star"></i> ${item.rating}`;
        document.getElementById('modalType').innerText = item.type === 'movie' ? '电影' : '电视剧';
        document.getElementById('modalPlot').innerText = item.plot;
        document.getElementById('modalCast').innerText = item.cast;
        document.getElementById('modalPoster').src = item.poster;
        document.getElementById('modalBackdrop').src = item.backdrop;
        
        const playBtn = document.getElementById('mainPlayBtn');
        playBtn.onclick = () => this.openPlayer(item, 1);

        // 处理电视剧分集
        const episodeSection = document.getElementById('episodeSection');
        const episodeList = document.getElementById('episodeList');
        episodeList.innerHTML = '';

        if (item.type === 'tv') {
            episodeSection.classList.remove('hidden');
            // 模拟生成分集按钮
            const count = item.episodes || 10;
            for (let i = 1; i <= count; i++) {
                const epBtn = document.createElement('button');
                epBtn.className = 'bg-gray-700 hover:bg-red-600 text-white text-sm py-2 rounded transition';
                epBtn.innerText = i;
                epBtn.onclick = () => this.openPlayer(item, i);
                episodeList.appendChild(epBtn);
            }
        } else {
            episodeSection.classList.add('hidden');
        }

        document.getElementById('detailModal').classList.remove('hidden');
    },

    closeModal: function() {
        document.getElementById('detailModal').classList.add('hidden');
    },

    // 打开播放器 - 支持 MP4, WebM, HLS (m3u8)
    openPlayer: function(item, episode) {
        this.closeModal(); // 关闭详情弹窗
        
        const playerModal = document.getElementById('playerModal');
        const video = document.getElementById('videoPlayer');
        const title = document.getElementById('playerTitle');
        const prevBtn = document.getElementById('prevEpBtn');
        const nextBtn = document.getElementById('nextEpBtn');
        
        this.state.currentPlayingItem = item;
        this.state.currentEpisode = episode;

        let displayName = item.title;
        if (item.type === 'tv') {
            displayName += ` 第${episode}集`;
            prevBtn.classList.remove('hidden');
            nextBtn.classList.remove('hidden');
        } else {
            prevBtn.classList.add('hidden');
            nextBtn.classList.add('hidden');
        }
        
        title.innerText = displayName;
        
        // 处理视频源
        let source = item.videoUrl;
        // 如果是电视剧且有分集地址，使用分集地址
        if (item.type === 'tv' && item.episodeUrls && item.episodeUrls[episode]) {
            source = item.episodeUrls[episode];
        }
        
        // 先清理之前的播放器实例
        this.cleanupPlayer();

        // 检测是否为 HLS (m3u8) 流
        if (source.endsWith('.m3u8')) {
            if (Hls.isSupported()) {
                const hls = new Hls();
                this.state.hlsInstance = hls; // 保存实例
                hls.loadSource(source);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    video.play().catch(e => console.log("Auto-play prevented:", e));
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Safari 原生支持 HLS
                video.src = source;
                video.play().catch(e => console.log("Auto-play prevented:", e));
            } else {
                alert("您的浏览器不支持 HLS 播放");
                return;
            }
        } else {
            // 标准网页视频格式 (MP4, WebM 等)
            video.src = source;
            video.play().catch(e => console.log("Auto-play prevented:", e));
        }
        
        video.poster = item.backdrop;
        playerModal.classList.remove('hidden');
    },

    // 清理播放器资源
    cleanupPlayer: function() {
        const video = document.getElementById('videoPlayer');
        video.pause();
        video.removeAttribute('src'); // 彻底清除源
        video.load(); // 重置视频元素
        
        if (this.state.hlsInstance) {
            this.state.hlsInstance.destroy();
            this.state.hlsInstance = null;
        }
    },

    playNextEpisode: function() {
        if (!this.state.currentPlayingItem) return;
        const maxEp = this.state.currentPlayingItem.episodes || 10;
        if (this.state.currentEpisode < maxEp) {
            this.openPlayer(this.state.currentPlayingItem, this.state.currentEpisode + 1);
        }
    },

    playPrevEpisode: function() {
        if (!this.state.currentPlayingItem) return;
        if (this.state.currentEpisode > 1) {
            this.openPlayer(this.state.currentPlayingItem, this.state.currentEpisode - 1);
        }
    },

    closePlayer: function() {
        this.cleanupPlayer();
        document.getElementById('playerModal').classList.add('hidden');
    },
    
    playHero: function() {
        const item = this.mockData[0];
        this.openPlayer(item, 1);
    }
};

// 启动应用
document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
