import numpy as np
import pandas as pd

FEATURE_COLS = [
    'day_of_week', 'month', 'week_of_year', 'day_of_month',
    'is_bd_weekend', 'lag_1', 'lag_7', 'lag_14', 'lag_30',
    'rolling_7', 'rolling_30', 'days_since_start',
]

def add_time_features(df: pd.DataFrame, value_col: str) -> pd.DataFrame:
    """
    Add temporal and lag features to a daily time-series DataFrame.
    Requires columns: 'date' (datetime), value_col (float).
    BD weekend = Friday (4) and Saturday (5) in Python's dayofweek (Mon=0).
    """
    df = df.copy().sort_values('date').reset_index(drop=True)

    df['day_of_week']      = df['date'].dt.dayofweek
    df['month']            = df['date'].dt.month
    df['week_of_year']     = df['date'].dt.isocalendar().week.astype(int)
    df['day_of_month']     = df['date'].dt.day
    df['is_bd_weekend']    = df['day_of_week'].isin([4, 5]).astype(int)
    df['days_since_start'] = (df['date'] - df['date'].min()).dt.days

    df['lag_1']     = df[value_col].shift(1)
    df['lag_7']     = df[value_col].shift(7)
    df['lag_14']    = df[value_col].shift(14)
    df['lag_30']    = df[value_col].shift(30)
    df['rolling_7'] = df[value_col].shift(1).rolling(7,  min_periods=1).mean()
    df['rolling_30']= df[value_col].shift(1).rolling(30, min_periods=1).mean()

    return df

def features_for_next_date(next_date: pd.Timestamp, history: pd.DataFrame, value_col: str) -> np.ndarray:
    """
    Compute the feature vector for a single future date given rolling history.
    history must have 'date' and value_col columns, sorted ascending.
    """
    rev = history.set_index('date')[value_col]

    def lag(days):
        target = next_date - pd.Timedelta(days=days)
        return float(rev.get(target, 0.0))

    rolling_7  = float(rev.iloc[-7:].mean())  if len(rev) >= 7  else float(rev.mean())
    rolling_30 = float(rev.iloc[-30:].mean()) if len(rev) >= 30 else float(rev.mean())
    days_since = (next_date - history['date'].min()).days

    return np.array([[
        next_date.dayofweek,
        next_date.month,
        next_date.isocalendar()[1],
        next_date.day,
        1 if next_date.dayofweek in [4, 5] else 0,
        lag(1), lag(7), lag(14), lag(30),
        rolling_7, rolling_30,
        days_since,
    ]])