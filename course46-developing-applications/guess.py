import random


def main():
    answer = random.randint(1, 100)
    attempts = 0
    print("1から100までの数字を当ててみてください。")

    while True:
        raw = input("数字を入力してください: ")
        try:
            guess = int(raw)
        except ValueError:
            print("数値を入力してください。")
            continue

        attempts += 1
        if guess < answer:
            print("もっと大きい数字です")
        elif guess > answer:
            print("もっと小さい数字です")
        else:
            print(f"正解！ {attempts}回で当てました")
            break


if __name__ == "__main__":
    main()
