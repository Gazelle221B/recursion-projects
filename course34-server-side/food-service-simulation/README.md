# Food Service Simulation

Recursion「Server Side Development Project」の課題。レストランの注文システムを PHP の OOP（カプセル化・抽象化・継承・ポリモーフィズム）でモデル化するシミュレーション。

## 構成

- `FoodItems/FoodItem.php` — 食品アイテムの抽象クラス（名称・説明・価格）。カテゴリは具象クラスの定数クラス変数と静的メソッドで取得する
  - `FoodItems/Pizza.php` / `Burger.php` / `Pasta.php` — カテゴリ別の具象クラス
- `Persons/Person.php` — 人物の抽象クラス（名前・年齢・住所）
  - `Persons/Customer.php` — `interestedCategories(Restaurant)` で、そのレストランが提供するカテゴリのうち興味のあるものを返す
  - `Persons/Employee.php` / `Cashier.php` / `Chef.php` — レジは FoodOrder と Invoice を生成し、シェフは注文を調理する
- `Restaurants/Restaurant.php` — メニューとスタッフを保持し、`order()` でレジ→シェフへ委任して Invoice を返す
- `Restaurants/FoodOrder.php` / `Invoice.php` — 注文（合計金額・所要時間）と請求書（最終価格・注文時刻・完成までの時間）

## 実行

```bash
php main.php
```

シミュレーション例: レストラン saizeriya（シェフ Invah、レジ Nadia、メニューは CheeseBurger / Fettuccine / HawaiianPizza / Spaghetti）に、客の Tom が興味のあるカテゴリで注文し、請求書が表示される。
