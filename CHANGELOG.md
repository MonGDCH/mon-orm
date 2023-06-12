### 更新日志

> 所有值得注意的版本信息都将记录在该文件中

#### [2.5.6](#) (2023-06-12)

- 优化`join`查询方法
- 优化`in`、`not in`查询条件，支持`Raw`对象
- 增强对Gaia框架的支持


#### [2.5.4](#) (2022-11-15)

- 增强对Gaia框架的支持

#### [2.5.1](#) (2022-07-11)

- 优化代码注解
- 优化文档

#### [2.5.0](https://github.com/MonGDCH/mon-orm/commit/43bf507e8cfcae4ce79154a377ac8b0db2bbfa0e) (2022-07-11)

- 优化代码
- 断线自动重连默认使用长链接
- 支持分布式部署数据库读写分离(通过配置文件自动识别)
- 版本升级修改了`conncet`方法返回值，生产环境请谨慎升级

#### [2.4.7](https://github.com/MonGDCH/mon-orm/commit/351d9c8e186a79d59d08e323bcf3000b9079ce37) (2022-01-12)

- 优化代码注解

#### [2.4.6](https://github.com/MonGDCH/mon-orm/commit/9451d3bfb58ce627d5b819784c5850b703a1f75f) (2021-08-19)

- 优化断线重连机制，通过`Db::reconnect(true)`进行全局设置

#### [2.4.5](https://github.com/MonGDCH/mon-orm/commit/060c72e47097d3d2c3378e6247720224f36e5737) (2021-07-26)

- 修复PDOException在部分mysql版本中获取的异常code为字符串，导致DbException异常的BUG
- 优化Query类model实例绑定

#### [2.4.4](https://github.com/MonGDCH/mon-orm/commit/10091416a8aefa1376ca677540a74b67e6e1f92d) (2021-06-09)

- 优化insertAll的sql构建，模型增加saveAll方法，支持自动完成。

#### [2.4.3](https://github.com/MonGDCH/mon-orm/commit/3686537b1b4c70a624334c97bd98137fa4b07058) (2021-05-29)

- 优化异常处理信息，绑定链接实例

#### [2.4.2](https://github.com/MonGDCH/mon-orm/commit/f7c69b521f52589ee07b3d2cb24d56afa700fffd) (2021-05-28)

- 优化异常处理，修改MonDbException为DbException
- 优化模型对跨库配置的支持
- 优化配置文件，采用二级数组配置，默认使用default节点的配置信息

#### [2.4.1](https://github.com/MonGDCH/mon-orm/commit/6a87e779de6c87872e3c3169a98d9acc7ea24084) (2021-04-13)

- 优化代码
- 修改Db::event方法为Db::listen方法，更具语义性

#### [2.4.0](https://github.com/MonGDCH/mon-orm/commit/5544ad102f0bebbc070b950ccbd62183e3eb7ae3) (2021-04-03)

- 优化代码，增强where查询条件查询方式，支持多维数组查询
- 增加\mon\orm\db\Raw原生表达式对象，增强原生查询能力
- 优化模型get、all查询，查询数据为空时，返回null而非空对象
- 模型增加内置验证器，可通过定义validate属性设置绑定的验证器，通过validate方法获取验证器
- 优化DB事件，支持单个事件绑定多个回调，Db::event方法改为Db::listen方法

#### [2.3.2](https://github.com/MonGDCH/mon-orm/commit/0ac2cafec43abe80c134e5f2f82ca2c7142ef636) (2021-03-02)

- 优化代码，增强注解
- 增加对分布式事务XA支持（注意：使用XA事务无法使用本地事务及锁表操作，更无法支持事务嵌套）

#### [2.3.1](https://github.com/MonGDCH/mon-orm/commit/5b53eb5b5273760c724f12a6ba6a82049f6648b9) (2020-11-20)

- 修复模型数据自动完成时，参数值缺失的问题

#### [2.3.0](https://github.com/MonGDCH/mon-orm/commit/a1c56255b6d19d7c5641e946ad63dd9274ab1886) (2020-09-25)

- 优化代码，增强注解
- 增加断线重连配置参数(break_reconnect，默认断线不自动重连)

#### [2.2.2](https://github.com/MonGDCH/mon-orm/commit/b2c726792f5000ca2b3c7fbc10da9c46193cb274) (2020-08-13)

- 优化文档，增强注解

#### [2.2.1](https://github.com/MonGDCH/mon-orm/commit/d50d2f64f656546cb547062f859d0d7dd078728a) (2020-07-23)

- 优化文档，增强注解

#### [2.2.0](https://github.com/MonGDCH/mon-orm/commit/f66b064be78e22da9941b554c44c2637ad1eac91) (2020-07-21)

- 增加模型类readonly属性及allowField方法，用于在调用save方法时定义只读字段及过滤无效的操作字段
- 优化代码，增强注解

#### [2.1.7](https://github.com/MonGDCH/mon-orm/commit/f26199e8070e2f30b619c4ed61625f55df9e675c) (2020-07-20)

- 优化代码，修正DB类实例返回Query实例导致Connect实例部分方法无法直接使用DB类实例调用

#### [2.1.6](https://github.com/MonGDCH/mon-orm/commit/3cb830c71e580756cacc29f46d19a95f150270ee) (2020-05-30)

- 优化代码，增强注解

#### [2.1.5](https://github.com/MonGDCH/mon-orm/commit/c44ea078efa71e7804edf8ab48a373c327996c3b) (2020-04-06)

- 修复在严格模式下缺失option的错误

#### [2.1.4](https://github.com/MonGDCH/mon-orm/commit/b7a32914d715db461876695bfb07396a3d3939f9) (2020-04-05)

- 优化代码，增加注解
- 增加duplicate、using、extra、page等方法

#### [2.1.3](https://github.com/MonGDCH/mon-orm/commit/50ca84792f6d2c200e11de51545727b9c49162ae) (2020-04-01)

- 优化代码，增强注解。

#### [2.1.2](https://github.com/MonGDCH/mon-orm/commit/bbd9933164fcaa754f442d6e9d13ee531000e8b4) (2019-09-22)

- 优化代码
- 增加connect、query、execute事件等DB类的全局事件绑定

#### [2.1.1](https://github.com/MonGDCH/mon-orm/commit/c30cab8c19fb42c2038b4ea23214a2f9c9bb92c4) (2019-07-05)

- 优化代码
- 增加DB类的事件绑定，分别对应select、update、insert、delete事件

#### [2.1.0](https://github.com/MonGDCH/mon-orm/commit/80fc53eb7efc32a449267c521c8a0137f5b1ee40) (2019-03-26)

- 修复Connection对象getError方法与Model对象getError方法重名的BUG, 获取Connection::getError方法改为getQueryError
- 调整命名空间，改为mon\orm
- 优化代码结构

#### [2.0.3](https://github.com/MonGDCH/mon-orm/commit/1ccd25a2e85fd23776cb2de09e893c9a26b6eacd) (2018-11-21)

- 修复未定义自动处理的字段也自动处理的BUG

#### [2.0.2](https://github.com/MonGDCH/mon-orm/commit/fda16594acc461188b0eec5743efc4937e614491) (2018-11-13)

- 修复批量写入insertAll写入BUG

#### [2.0.1](https://github.com/MonGDCH/mon-orm/commit/a522ac6ba4380bbf316819551dbd48f0fb89da90) (2018-08-03)

- 优化模型查询结果集
- 优化自动完成设置器及获取器
- 优化代码结构，修复lock查询无效的问题
- 优化模型scope方法。支持传参
- 优化Query类查询方法

#### [2.0.0](https://github.com/MonGDCH/mon-orm/commit/544b4d8b683f333ebafe8cabd16419cfc22ed0b3) (2018-07-29)

- 优化事务支持
- 增强模型对象，增加save、get、all、scope等模型方法
- 增强模型功能，增加设置器、获取器的功能
- 优化查询结果集

#### [1.0.3](https://github.com/MonGDCH/mon-orm/commit/f5ddca93038f067159e6629d00b4ee645f32a5a6) (2018-07-15)

- 修正count，svg, sum等方法无法使用debug获取查询语句
- 优化代码结构

#### [1.0.2](https://github.com/MonGDCH/mon-orm/commit/c4ac3479e115b5220d258dffd82d3d904bea93ca) (2018-07-07)

- 增加setInc,setDec字段自增自减查询方法
- 修复find查询下debug方法无效的bug，优化闭包查询。

#### [1.0.1](https://github.com/MonGDCH/mon-orm/commit/379172dbff3f24da3aba909a605b4ba9b7a5e611) (2018-07-03)

- 优化代码
- 修正支持自定义PDO返回数据类型









